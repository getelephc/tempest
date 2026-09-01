import assert from 'node:assert/strict';
import { createHash } from 'node:crypto';
import { readFile, readdir } from 'node:fs/promises';
import path from 'node:path';
import process from 'node:process';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const requireApplied = process.argv.includes('--require-applied');
const requireCleanSource = process.argv.includes('--require-clean-source');

function compareStrings(left, right) {
    return left < right ? -1 : left > right ? 1 : 0;
}

async function listPatches(directory, optional = false) {
    let entries;

    try {
        entries = await readdir(directory, { withFileTypes: true });
    } catch (error) {
        if (optional && error.code === 'ENOENT') {
            return [];
        }
        throw error;
    }
    const files = [];

    for (const entry of entries.sort((left, right) => compareStrings(left.name, right.name))) {
        const absolute = path.join(directory, entry.name);

        if (entry.isDirectory()) {
            files.push(...await listPatches(absolute));
        } else if (entry.name.endsWith('.patch')) {
            files.push(absolute);
        }
    }

    return files;
}

function gitBlobHash(contents) {
    const header = Buffer.from(`blob ${contents.length}\0`);
    return createHash('sha1').update(header).update(contents).digest('hex');
}

async function targetState(record) {
    try {
        const contents = await readFile(path.join(root, record.target));
        const hash = gitBlobHash(contents);

        if (hash === record.originalHash) {
            return 'clean';
        }
        if (hash === record.patchedHash) {
            return 'applied';
        }

        return 'divergent';
    } catch (error) {
        if (error.code === 'ENOENT') {
            return 'missing';
        }
        throw error;
    }
}

const series = [
    { kind: 'source', root: path.join(root, 'patches', 'source') },
    { kind: 'vendor', root: path.join(root, 'patches', 'vendor') },
    { kind: 'runtime', root: path.join(root, 'patches', 'runtime') },
];
const records = [];
const digest = createHash('sha256');
const targets = new Set();

for (const currentSeries of series) {
    const patches = await listPatches(currentSeries.root, currentSeries.kind === 'vendor');
    if (currentSeries.kind !== 'vendor') {
        assert.ok(patches.length > 0, `${currentSeries.kind} patch corpus is empty.`);
    }

    for (const absolute of patches) {
        const relative = path.relative(root, absolute);
        const contents = await readFile(absolute);
        const text = contents.toString('utf8');
        const patchTargets = [...text.matchAll(/^\+\+\+ b\/(.+)$/gm)].map((match) => match[1]);
        const indexLines = [...text.matchAll(/^index ([0-9a-f]{40})\.\.([0-9a-f]{40}) \d+$/gm)];

        assert.equal(patchTargets.length, 1, `Patch must have exactly one target: ${relative}`);
        assert.equal(indexLines.length, 1, `Patch must have exactly one full index line: ${relative}`);

        const target = patchTargets[0];
        let expectedPath;

        if (currentSeries.kind === 'vendor') {
            expectedPath = path.join('patches', 'vendor', `${target.replace(/^vendor\//, '')}.patch`);
        } else if (currentSeries.kind === 'runtime') {
            expectedPath = path.join('patches', 'runtime', `${target.replace(/^elephc\/runtime\//, '')}.patch`);
        } else {
            expectedPath = path.join('patches', 'source', `${target}.patch`);
        }

        assert.equal(relative, expectedPath, `Patch path does not mirror its target: ${relative}`);
        assert.ok(!targets.has(target), `Duplicate patch target: ${target}`);
        targets.add(target);

        if (currentSeries.kind === 'vendor') {
            assert.match(target, /^vendor\/.+\.php$/, `Unsupported vendor target: ${target}`);
        } else if (currentSeries.kind === 'runtime') {
            assert.equal(
                target,
                'elephc/runtime/vendor/tempest/framework/composer.json',
                `Unsupported runtime target: ${target}`,
            );
        } else {
            const isPhpSource = /^(src|packages|tests)\/.+\.php$/.test(target);
            const isMapperDocumentation = target === 'docs/2-features/01-mapper.md';
            assert.ok(isPhpSource || isMapperDocumentation, `Unsupported source target: ${target}`);
        }

        const record = {
            kind: currentSeries.kind,
            relative,
            target,
            originalHash: indexLines[0][1],
            patchedHash: indexLines[0][2],
        };
        record.state = await targetState(record);
        records.push(record);

        digest.update(relative);
        digest.update('\0');
        digest.update(contents);
        digest.update('\0');
    }
}

const expectedRuntimeLockHash = (await readFile(path.join(root, 'patches', 'runtime.composer-lock.sha256'), 'utf8')).trim();
const runtimeLockHash = createHash('sha256').update(await readFile(path.join(root, 'elephc', 'runtime', 'composer.lock'))).digest('hex');
assert.equal(runtimeLockHash, expectedRuntimeLockHash, 'Runtime composer.lock does not match the runtime patch baseline.');

const sourceBaseline = (await readFile(path.join(root, 'patches', 'source.baseline'), 'utf8')).trim();
assert.match(sourceBaseline, /^[0-9a-f]{40}$/, 'Source baseline must be a full Git commit SHA.');

for (const record of records) {
    assert.notEqual(record.state, 'divergent', `Divergent patch target: ${record.target}`);

    if (record.kind === 'source') {
        assert.notEqual(record.state, 'missing', `Missing source patch target: ${record.target}`);
    }
    if (requireApplied) {
        assert.equal(record.state, 'applied', `Patch is not applied: ${record.target}`);
    }
    if (requireCleanSource && record.kind === 'source') {
        assert.equal(record.state, 'clean', `Source patch is already applied: ${record.target}`);
    }
}

function count(kind, state) {
    return records.filter((record) => record.kind === kind && record.state === state).length;
}

console.log(`Source compatibility patches: ${records.filter(({ kind }) => kind === 'source').length}`);
console.log(`Vendor compatibility patches: ${records.filter(({ kind }) => kind === 'vendor').length}`);
console.log(`Runtime compatibility patches: ${records.filter(({ kind }) => kind === 'runtime').length}`);
console.log(`Source state: ${count('source', 'clean')} clean, ${count('source', 'applied')} applied`);
console.log(`Vendor state: ${count('vendor', 'clean')} clean, ${count('vendor', 'applied')} applied, ${count('vendor', 'missing')} missing`);
console.log(`Runtime state: ${count('runtime', 'clean')} clean, ${count('runtime', 'applied')} applied, ${count('runtime', 'missing')} missing`);
console.log(`Source baseline: ${sourceBaseline}`);
console.log(`Runtime Composer lock SHA-256: ${runtimeLockHash}`);
console.log(`Corpus SHA-256: ${digest.digest('hex')}`);
