import fs from 'node:fs';

const rootPackage = JSON.parse( fs.readFileSync( new URL( '../package.json', import.meta.url ) ) );
const rootLock = JSON.parse( fs.readFileSync( new URL( '../package-lock.json', import.meta.url ) ) );
const auditPackage = JSON.parse( fs.readFileSync( new URL( '../runtime-assets/package.json', import.meta.url ) ) );
const auditLock = JSON.parse( fs.readFileSync( new URL( '../runtime-assets/package-lock.json', import.meta.url ) ) );

const packageName = 'fontawesome-iconpicker';
const rootConstraint = rootPackage.devDependencies[ packageName ];
const auditConstraint = auditPackage.dependencies[ packageName ];
const rootVersion = rootLock.packages[ `node_modules/${ packageName }` ].version;
const auditVersion = auditLock.packages[ `node_modules/${ packageName }` ].version;

if (
	rootConstraint !== rootVersion ||
	auditConstraint !== auditVersion ||
	rootVersion !== auditVersion
) {
	throw new Error( 'The shipped browser asset source and runtime audit manifest are not pinned to the same version.' );
}

console.log( `Verified runtime asset audit coverage for ${ packageName } ${ rootVersion }.` );
