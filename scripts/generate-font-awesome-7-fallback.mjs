#!/usr/bin/env node

import { createHash } from 'node:crypto';
import {
	copyFile,
	mkdir,
	mkdtemp,
	readdir,
	readFile,
	rm,
	stat,
	writeFile,
} from 'node:fs/promises';
import { tmpdir } from 'node:os';
import { dirname, join, relative, resolve, sep } from 'node:path';
import { extract, list } from 'tar';

const PACKAGE_NAME = '@fortawesome/fontawesome-free';
const PACKAGE_LICENSE = '(CC-BY-4.0 AND OFL-1.1 AND MIT)';
const MAX_ARCHIVE_BYTES = 8 * 1024 * 1024;
const MAX_REGISTRY_BYTES = 512 * 1024;
const MAX_OUTPUT_BYTES = 2 * 1024 * 1024;
const OUTPUT_ROOT = resolve('inc/font-awesome-7-fallback');

const sourceFiles = new Map([
	['package/package.json', { limit: 32 * 1024 }],
	['package/metadata/icon-families.json', { limit: 6 * 1024 * 1024 }],
	['package/LICENSE.txt', { limit: 32 * 1024, output: 'LICENSE.txt' }],
	['package/css/all.min.css', { limit: 256 * 1024, output: 'css/all.min.css' }],
	['package/css/v4-font-face.min.css', { limit: 16 * 1024, output: 'css/v4-font-face.min.css' }],
	['package/css/v4-shims.min.css', { limit: 64 * 1024, output: 'css/v4-shims.min.css' }],
	['package/css/v5-font-face.min.css', { limit: 16 * 1024, output: 'css/v5-font-face.min.css' }],
	['package/webfonts/fa-brands-400.woff2', { limit: 256 * 1024, output: 'webfonts/fa-brands-400.woff2' }],
	['package/webfonts/fa-regular-400.woff2', { limit: 256 * 1024, output: 'webfonts/fa-regular-400.woff2' }],
	['package/webfonts/fa-solid-900.woff2', { limit: 256 * 1024, output: 'webfonts/fa-solid-900.woff2' }],
	['package/webfonts/fa-v4compatibility.woff2', { limit: 64 * 1024, output: 'webfonts/fa-v4compatibility.woff2' }],
]);

const productionAssets = [...sourceFiles.values()]
	.filter((entry) => entry.output && entry.output !== 'LICENSE.txt')
	.map((entry) => entry.output)
	.sort();

const requiredCss = productionAssets.filter((path) => path.startsWith('css/'));
const requiredWebfonts = productionAssets.filter((path) => path.startsWith('webfonts/'));

function fail(message) {
	throw new Error(message);
}

function hash(data, algorithm, encoding = 'hex') {
	return createHash(algorithm).update(data).digest(encoding);
}

function stableJson(value) {
	return `${JSON.stringify(value, null, 2)}\n`;
}

function isValidName(value) {
	return typeof value === 'string' && value.length <= 128 && /^[a-z0-9]+(?:-[a-z0-9]+)*$/.test(value);
}

function assertSafeArchivePath(path) {
	if (
		typeof path !== 'string' ||
		path.length === 0 ||
		path.includes('\\') ||
		path.includes('\0') ||
		path.startsWith('/') ||
		path.split('/').includes('..')
	) {
		fail(`Unsafe npm archive path: ${JSON.stringify(path)}`);
	}
}

async function readBoundedResponse(response, limit, description) {
	if (!response.ok) {
		fail(`${description} returned HTTP ${response.status}.`);
	}

	const contentLength = Number(response.headers.get('content-length'));
	if (Number.isFinite(contentLength) && contentLength > limit) {
		fail(`${description} exceeded its declared size limit.`);
	}

	if (!response.body) {
		fail(`${description} returned no body.`);
	}

	const chunks = [];
	let bytes = 0;
	for await (const chunk of response.body) {
		bytes += chunk.length;
		if (bytes > limit) {
			fail(`${description} exceeded its response size limit.`);
		}
		chunks.push(chunk);
	}

	return Buffer.concat(chunks, bytes);
}

async function fetchRegistryMetadata(version) {
	const registryUrl = `https://registry.npmjs.org/%40fortawesome%2Ffontawesome-free/${version}`;
	const body = await readBoundedResponse(
		await fetch(registryUrl, { headers: { Accept: 'application/json' }, redirect: 'error' }),
		MAX_REGISTRY_BYTES,
		'npm registry metadata',
	);

	let metadata;
	try {
		metadata = JSON.parse(body.toString('utf8'));
	} catch {
		fail('npm registry metadata was not valid JSON.');
	}

	if (metadata.name !== PACKAGE_NAME || metadata.version !== version) {
		fail('npm registry package identity did not match the requested exact release.');
	}

	if (
		!metadata.dist ||
		typeof metadata.dist.integrity !== 'string' ||
		!/^sha512-[A-Za-z0-9+/]+={0,2}$/.test(metadata.dist.integrity) ||
		typeof metadata.dist.shasum !== 'string' ||
		!/^[a-f0-9]{40}$/.test(metadata.dist.shasum) ||
		typeof metadata.dist.tarball !== 'string'
	) {
		fail('npm registry distribution metadata was incomplete or malformed.');
	}

	const tarballUrl = new URL(metadata.dist.tarball);
	const expectedPath = `/@fortawesome/fontawesome-free/-/fontawesome-free-${version}.tgz`;
	if (tarballUrl.protocol !== 'https:' || tarballUrl.hostname !== 'registry.npmjs.org' || tarballUrl.pathname !== expectedPath || tarballUrl.search || tarballUrl.hash) {
		fail('npm registry tarball URL did not match the exact official package release.');
	}

	return { metadata, registryUrl };
}

async function inspectArchive(archivePath) {
	const found = new Map();
	await list({
		file: archivePath,
		strict: true,
		onReadEntry(entry) {
			assertSafeArchivePath(entry.path);
			const rule = sourceFiles.get(entry.path);
			if (!rule) {
				return;
			}

			if (entry.type !== 'File' && entry.type !== 'OldFile') {
				fail(`Allowlisted npm archive entry was not a regular file: ${entry.path}`);
			}

			if (found.has(entry.path)) {
				fail(`Allowlisted npm archive entry was duplicated: ${entry.path}`);
			}

			if (!Number.isSafeInteger(entry.size) || entry.size < 1 || entry.size > rule.limit) {
				fail(`Allowlisted npm archive entry exceeded its size limit: ${entry.path}`);
			}

			found.set(entry.path, entry.size);
		},
	});

	for (const path of sourceFiles.keys()) {
		if (!found.has(path)) {
			fail(`Required npm archive entry was missing: ${path}`);
		}
	}
}

async function extractAllowlist(archivePath, extractionRoot) {
	await extract({
		cwd: extractionRoot,
		file: archivePath,
		filter(path) {
			return sourceFiles.has(path);
		},
		preservePaths: false,
		strict: true,
		strip: 1,
	});

	for (const [sourcePath, rule] of sourceFiles) {
		const extractedPath = join(extractionRoot, sourcePath.replace(/^package\//, ''));
		const info = await stat(extractedPath);
		if (!info.isFile() || info.isSymbolicLink() || info.size < 1 || info.size > rule.limit) {
			fail(`Extracted npm file failed validation: ${sourcePath}`);
		}
	}
}

function buildReleaseMetadata(iconFamilies, version, assetBuffers) {
	if (!iconFamilies || typeof iconFamilies !== 'object' || Array.isArray(iconFamilies)) {
		fail('Font Awesome icon family metadata was malformed.');
	}

	const canonicalIds = Object.keys(iconFamilies).sort();
	if (canonicalIds.length < 1 || canonicalIds.length > 5000 || canonicalIds.some((id) => !isValidName(id))) {
		fail('Font Awesome icon family metadata contained invalid canonical IDs.');
	}

	const canonicalSet = new Set(canonicalIds);
	const aliasSet = new Set();
	const icons = [];

	for (const id of canonicalIds) {
		const source = iconFamilies[id];
		if (!source || typeof source !== 'object' || typeof source.label !== 'string' || source.label.trim() === '' || source.label.length > 200) {
			fail(`Font Awesome icon metadata was incomplete: ${id}`);
		}

		const aliases = source.aliases?.names ?? [];
		if (!Array.isArray(aliases)) {
			fail(`Font Awesome icon aliases were malformed: ${id}`);
		}

		const names = [...aliases].sort();
		for (const alias of names) {
			if (!isValidName(alias) || alias === id) {
				fail(`Font Awesome icon alias was invalid: ${id}`);
			}
			if (canonicalSet.has(alias) || aliasSet.has(alias)) {
				fail(`Font Awesome icon alias collided with another name: ${alias}`);
			}
			aliasSet.add(alias);
		}

		const memberships = source.familyStylesByLicense?.free;
		if (!Array.isArray(memberships) || memberships.length < 1) {
			fail(`Font Awesome icon had no Free family membership: ${id}`);
		}

		const seenMemberships = new Set();
		const free = memberships.map((membership) => {
			if (!membership || membership.family !== 'classic' || !['brands', 'regular', 'solid'].includes(membership.style)) {
				fail(`Font Awesome icon contained an unsupported Free family/style: ${id}`);
			}
			const key = `${membership.family}/${membership.style}`;
			if (seenMemberships.has(key)) {
				fail(`Font Awesome icon contained duplicate Free family membership: ${id}`);
			}
			seenMemberships.add(key);
			return { family: membership.family, style: membership.style };
		}).sort((left, right) => `${left.family}/${left.style}`.localeCompare(`${right.family}/${right.style}`));

		icons.push({
			id,
			label: source.label,
			aliases: { names },
			familyStylesByLicense: { free },
		});
	}

	const assets = requiredCss.map((path) => ({
		path,
		value: `sha512-${hash(assetBuffers.get(path), 'sha512', 'base64')}`,
	}));

	return {
		schema_version: 2,
		channel: '7.x',
		edition: 'free',
		source: 'fallback',
		release: {
			version,
			icons,
			srisByLicense: { free: assets },
		},
	};
}

async function walkFiles(root, current = root) {
	const files = [];
	for (const entry of await readdir(current, { withFileTypes: true })) {
		const absolute = join(current, entry.name);
		if (entry.isSymbolicLink()) {
			fail(`Unexpected symbolic link in generated fallback: ${relative(root, absolute)}`);
		}
		if (entry.isDirectory()) {
			files.push(...await walkFiles(root, absolute));
		} else if (entry.isFile()) {
			files.push(relative(root, absolute).split(sep).join('/'));
		} else {
			fail(`Unexpected filesystem entry in generated fallback: ${relative(root, absolute)}`);
		}
	}
	return files.sort();
}

async function publishOutput(stagingRoot, expectedInventory) {
	await mkdir(OUTPUT_ROOT, { recursive: true });
	const existing = await walkFiles(OUTPUT_ROOT);
	for (const path of existing) {
		if (!expectedInventory.includes(path)) {
			fail(`Refusing to remove unexpected existing fallback file: ${path}`);
		}
	}

	for (const path of expectedInventory) {
		const destination = join(OUTPUT_ROOT, path);
		await mkdir(dirname(destination), { recursive: true });
		await copyFile(join(stagingRoot, path), destination);
	}

	const published = await walkFiles(OUTPUT_ROOT);
	if (JSON.stringify(published) !== JSON.stringify(expectedInventory)) {
		fail('Published fallback inventory did not match the generated allowlist.');
	}
}

async function main() {
	const args = process.argv.slice(2);
	if (args.length !== 1 || !/^(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)$/.test(args[0])) {
		fail('Usage: npm run generate:font-awesome-7-fallback -- <exact-version>');
	}

	const version = args[0];
	if (!version.startsWith('7.')) {
		fail('The fallback generator accepts only an exact Font Awesome 7 version.');
	}

	const workRoot = await mkdtemp(join(tmpdir(), 'bfal-fa7-'));
	try {
		const { metadata, registryUrl } = await fetchRegistryMetadata(version);
		const archive = await readBoundedResponse(
			await fetch(metadata.dist.tarball, { redirect: 'error' }),
			MAX_ARCHIVE_BYTES,
			'npm package tarball',
		);

		const archiveIntegrity = `sha512-${hash(archive, 'sha512', 'base64')}`;
		if (archiveIntegrity !== metadata.dist.integrity || hash(archive, 'sha1') !== metadata.dist.shasum) {
			fail('npm package tarball integrity did not match registry metadata.');
		}

		const archivePath = join(workRoot, 'package.tgz');
		const extractionRoot = join(workRoot, 'extracted');
		const stagingRoot = join(workRoot, 'output');
		await writeFile(archivePath, archive);
		await mkdir(extractionRoot);
		await mkdir(stagingRoot);
		await inspectArchive(archivePath);
		await extractAllowlist(archivePath, extractionRoot);

		const packageJson = JSON.parse(await readFile(join(extractionRoot, 'package.json'), 'utf8'));
		if (packageJson.name !== PACKAGE_NAME || packageJson.version !== version || packageJson.license !== PACKAGE_LICENSE) {
			fail('Extracted npm package identity or license did not match the approved package.');
		}

		const license = await readFile(join(extractionRoot, 'LICENSE.txt'));
		const licenseText = license.toString('utf8');
		for (const marker of ['Font Awesome Free License', '# Icons: CC BY 4.0 License', '# Fonts: SIL OFL 1.1 License', '# Code: MIT License']) {
			if (!licenseText.includes(marker)) {
				fail(`Extracted npm license was missing required text: ${marker}`);
			}
		}

		const assetBuffers = new Map();
		for (const path of productionAssets) {
			const data = await readFile(join(extractionRoot, path));
			assetBuffers.set(path, data);
			await mkdir(dirname(join(stagingRoot, path)), { recursive: true });
			await writeFile(join(stagingRoot, path), data);
		}

		for (const fontPath of requiredWebfonts) {
			const reference = `../${fontPath}`;
			if (![...requiredCss].some((cssPath) => assetBuffers.get(cssPath).includes(reference))) {
				fail(`Required webfont was not referenced by packaged CSS: ${fontPath}`);
			}
		}

		const iconFamilies = JSON.parse(await readFile(join(extractionRoot, 'metadata/icon-families.json'), 'utf8'));
		const releaseRecord = buildReleaseMetadata(iconFamilies, version, assetBuffers);
		const metadataJson = Buffer.from(stableJson(releaseRecord));
		if (metadataJson.length > MAX_OUTPUT_BYTES) {
			fail('Generated fallback metadata exceeded the schema response limit.');
		}

		const attribution = Buffer.from(
			`# Font Awesome Free ${version} attribution\n\n` +
			`This fallback was generated from the exact official npm package \`${PACKAGE_NAME}@${version}\`.\n\n` +
			'Font Awesome Free icons are licensed under CC BY 4.0, fonts under SIL OFL 1.1, and code under MIT. ' +
			'See `LICENSE.txt` for the complete upstream license and attribution terms.\n',
		);

		await writeFile(join(stagingRoot, 'metadata.json'), metadataJson);
		await writeFile(join(stagingRoot, 'LICENSE.txt'), license);
		await writeFile(join(stagingRoot, 'ATTRIBUTION.md'), attribution);

		const hashedPaths = ['ATTRIBUTION.md', 'LICENSE.txt', 'metadata.json', ...productionAssets].sort();
		const files = [];
		let aggregateBytes = 0;
		for (const path of hashedPaths) {
			const data = await readFile(join(stagingRoot, path));
			aggregateBytes += data.length;
			files.push({
				path,
				bytes: data.length,
				sha256: hash(data, 'sha256'),
				sha512: hash(data, 'sha512'),
			});
		}

		if (aggregateBytes > MAX_OUTPUT_BYTES) {
			fail('Generated fallback exceeded the aggregate output size limit.');
		}

		const provenance = {
			schema_version: 1,
			package: {
				name: PACKAGE_NAME,
				version,
				license: PACKAGE_LICENSE,
				registry_url: registryUrl,
				tarball_url: metadata.dist.tarball,
				integrity: metadata.dist.integrity,
				shasum: metadata.dist.shasum,
			},
			generator: {
				command: `npm run generate:font-awesome-7-fallback -- ${version}`,
				extracted_paths: [...sourceFiles.keys()].sort(),
			},
			files,
		};
		const provenanceJson = Buffer.from(stableJson(provenance));
		await writeFile(join(stagingRoot, 'provenance.json'), provenanceJson);
		await writeFile(join(stagingRoot, 'provenance.sha256'), `${hash(provenanceJson, 'sha256')}  provenance.json\n`);

		const expectedInventory = [
			'ATTRIBUTION.md',
			'LICENSE.txt',
			'metadata.json',
			...productionAssets,
			'provenance.json',
			'provenance.sha256',
		].sort();
		const stagedInventory = await walkFiles(stagingRoot);
		if (JSON.stringify(stagedInventory) !== JSON.stringify(expectedInventory)) {
			fail('Generated fallback contained an unexpected file or omitted an allowlisted file.');
		}

		await publishOutput(stagingRoot, expectedInventory);
		process.stdout.write(`Generated ${OUTPUT_ROOT} from ${PACKAGE_NAME}@${version}.\n`);
	} finally {
		await rm(workRoot, { recursive: true, force: true });
	}
}

main().catch((error) => {
	process.stderr.write(`Font Awesome 7 fallback generation failed: ${error.message}\n`);
	process.exitCode = 1;
});
