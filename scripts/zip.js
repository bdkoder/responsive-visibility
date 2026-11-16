/**
 * pnpm zip — build, then pack a WordPress-installable archive.
 *
 * Output: dist/responsive-visibility-<version>.zip with a single top-level folder
 * `responsive-visibility/` holding runtime files only. The staged folder stays in
 * dist/ for inspection. The version is read from the plugin header in
 * responsive-visibility.php. What is left out is listed in .buildignore.
 */
const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');

const root = path.resolve(__dirname, '..');
const slug = 'responsive-visibility';
const header = fs.readFileSync(path.join(root, `${slug}.php`), 'utf8');
const match = header.match(/^\s*\*\s*Version:\s*([0-9A-Za-z.\-]+)/m);

if (!match) {
	console.error(`zip: could not read "Version:" from ${slug}.php`);
	process.exit(1);
}

const version = match[1];
const dist = path.join(root, 'dist');
const stage = path.join(dist, slug);
const archive = path.join(dist, `${slug}-${version}.zip`);

const run = (cmd) => execSync(cmd, { cwd: root, stdio: 'inherit' });

run('pnpm build');

fs.rmSync(stage, { recursive: true, force: true });
fs.mkdirSync(stage, { recursive: true });

run(`rsync -a --exclude-from=.buildignore ./ '${stage}/'`);

fs.rmSync(archive, { force: true });
execSync(`zip -rq -X '${archive}' '${slug}'`, { cwd: dist, stdio: 'inherit' });

const size = (fs.statSync(archive).size / 1024).toFixed(0);
console.log(`\n✔ ${path.relative(root, archive)} (${size} KB)`);
