import assert from 'node:assert/strict';
import { createHash } from 'node:crypto';
import { readdir, readFile } from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const patchRoot = path.join(root, 'patches', 'vendor');

async function list(directory) {
    const entries = await readdir(directory, { withFileTypes: true });
    const files = [];

    for (const entry of entries) {
        const absolute = path.join(directory, entry.name);
        if (entry.isDirectory()) {
            files.push(...await list(absolute));
        } else {
            files.push(absolute);
        }
    }

    return files;
}

const patches = (await list(patchRoot)).sort();
assert.ok(patches.length > 0, 'The vendor patch corpus is empty.');

const digest = createHash('sha256');

for (const patch of patches) {
    assert.equal(path.extname(patch), '.patch', `Unexpected corpus file: ${patch}`);

    const content = await readFile(patch, 'utf8');
    const targets = [...content.matchAll(/^\+\+\+ b\/(.+)$/gm)].map((match) => match[1]);

    assert.ok(targets.length > 0, `Patch has no target: ${patch}`);
    for (const target of targets) {
        assert.match(target, /^vendor\/.+\.php$/, `Non-PHP vendor target: ${target}`);
    }

    digest.update(path.relative(root, patch));
    digest.update('\0');
    digest.update(content);
}

const expectedLockHash = (await readFile(path.join(root, 'patches', 'vendor.composer-lock.sha256'), 'utf8')).trim();
const lockHash = createHash('sha256').update(await readFile(path.join(root, 'composer.lock'))).digest('hex');
assert.equal(lockHash, expectedLockHash, 'composer.lock does not match the vendor patch baseline.');

console.log(`Vendor PHP patches: ${patches.length}`);
console.log(`Corpus SHA-256: ${digest.digest('hex')}`);
console.log(`Composer lock SHA-256: ${lockHash}`);
