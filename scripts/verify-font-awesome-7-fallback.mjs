#!/usr/bin/env node

import { createHash } from 'node:crypto';
import { readdir, readFile, stat } from 'node:fs/promises';
import { join, relative, resolve, sep } from 'node:path';

const root = resolve('inc/font-awesome-7-fallback');
const expectedAssets = [
	'css/all.min.css',
	'css/v4-font-face.min.css',
	'css/v4-shims.min.css',
	'css/v5-font-face.min.css',
	'webfonts/fa-brands-400.woff2',
	'webfonts/fa-regular-400.woff2',
	'webfonts/fa-solid-900.woff2',
	'webfonts/fa-v4compatibility.woff2',
];
const expectedInventory = [
	'ATTRIBUTION.md',
	'LICENSE.txt',
	'metadata.json',
	...expectedAssets,
	'provenance.json',
	'provenance.sha256',
].sort();

function fail(message) {
	throw new Error(message);
}

function hash(data, algorithm) {
	return createHash(algorithm).update(data).digest('hex');
}

async function walkFiles(current = root) {
	const files = [];
	for (const entry of await readdir(current, { withFileTypes: true })) {
		const absolute = join(current, entry.name);
		if (entry.isSymbolicLink()) {
			fail(`Fallback contains a symbolic link: ${relative(root, absolute)}`);
		}
		if (entry.isDirectory()) {
			files.push(...await walkFiles(absolute));
		} else if (entry.isFile()) {
			files.push(relative(root, absolute).split(sep).join('/'));
		} else {
			fail(`Fallback contains an unsupported filesystem entry: ${relative(root, absolute)}`);
		}
	}
	return files.sort();
}

async function main() {
	const inventory = await walkFiles();
	if (JSON.stringify(inventory) !== JSON.stringify(expectedInventory)) {
		fail('Fallback inventory did not match the production allowlist.');
	}

	const provenanceBytes = await readFile(join(root, 'provenance.json'));
	const checksum = (await readFile(join(root, 'provenance.sha256'), 'utf8')).trim();
	if (checksum !== `${hash(provenanceBytes, 'sha256')}  provenance.json`) {
		fail('Fallback provenance checksum did not match.');
	}

	const provenance = JSON.parse(provenanceBytes.toString('utf8'));
	if (
		provenance.schema_version !== 1 ||
		provenance.package?.name !== '@fortawesome/fontawesome-free' ||
		!/^7\.[0-9]+\.[0-9]+$/.test(provenance.package?.version) ||
		provenance.package?.license !== '(CC-BY-4.0 AND OFL-1.1 AND MIT)'
	) {
		fail('Fallback provenance package identity was invalid.');
	}

	const hashedPaths = ['ATTRIBUTION.md', 'LICENSE.txt', 'metadata.json', ...expectedAssets].sort();
	if (JSON.stringify(provenance.files.map((file) => file.path)) !== JSON.stringify(hashedPaths)) {
		fail('Fallback provenance file inventory was invalid.');
	}

	for (const file of provenance.files) {
		const absolute = join(root, file.path);
		const info = await stat(absolute);
		const data = await readFile(absolute);
		if (!info.isFile() || info.size !== file.bytes || hash(data, 'sha256') !== file.sha256 || hash(data, 'sha512') !== file.sha512) {
			fail(`Fallback file hash or size did not match provenance: ${file.path}`);
		}
	}

	const metadata = JSON.parse(await readFile(join(root, 'metadata.json'), 'utf8'));
	if (
		metadata.schema_version !== 2 ||
		metadata.channel !== '7.x' ||
		metadata.edition !== 'free' ||
		metadata.source !== 'fallback' ||
		metadata.release?.version !== provenance.package.version ||
		!Array.isArray(metadata.release?.icons) ||
		metadata.release.icons.length < 1
	) {
		fail('Fallback metadata identity was invalid.');
	}

	process.stdout.write(`Verified ${inventory.length} fallback files for @fortawesome/fontawesome-free@${provenance.package.version}.\n`);
}

main().catch((error) => {
	process.stderr.write(`Font Awesome 7 fallback verification failed: ${error.message}\n`);
	process.exitCode = 1;
});
