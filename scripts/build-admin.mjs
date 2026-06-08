/**
 * Builds admin assets: src/admin/{js,css} -> assets/{js,css}.
 * Emits both unminified (settings.js/.css) and minified (settings.min.js/.css).
 * Run: `node scripts/build-admin.mjs`  |  watch: add `--watch`.
 */
import { build, context } from 'esbuild';

const watch = process.argv.includes( '--watch' );

const sources = [
	{ in: 'src/admin/js/settings.js',   outDir: 'assets/js',  name: 'settings', ext: 'js' },
	{ in: 'src/admin/css/settings.css', outDir: 'assets/css', name: 'settings', ext: 'css' },
];

const common = {
	bundle: true,
	target: [ 'es2017' ],
	logLevel: 'info',
	legalComments: 'none',
};

// One unminified + one minified target per source file.
const targets = sources.flatMap( ( s ) => [
	{ entryPoints: [ s.in ], outfile: `${ s.outDir }/${ s.name }.${ s.ext }`,     minify: false },
	{ entryPoints: [ s.in ], outfile: `${ s.outDir }/${ s.name }.min.${ s.ext }`, minify: true },
] );

if ( watch ) {
	const ctxs = await Promise.all( targets.map( ( t ) => context( { ...common, ...t } ) ) );
	await Promise.all( ctxs.map( ( c ) => c.watch() ) );
	console.log( '[rv] esbuild watching src/admin -> assets …' );
} else {
	await Promise.all( targets.map( ( t ) => build( { ...common, ...t } ) ) );
	console.log( '[rv] admin assets built -> assets/js, assets/css' );
}
