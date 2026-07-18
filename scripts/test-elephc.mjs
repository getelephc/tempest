import assert from 'node:assert/strict';
import { spawn } from 'node:child_process';
import { access } from 'node:fs/promises';
import { createServer } from 'node:net';
import { once } from 'node:events';
import path from 'node:path';
import process from 'node:process';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const binary = path.join(root, 'elephc', 'server');

await access(binary);

const portProbe = createServer();
portProbe.listen(0, '127.0.0.1');
await once(portProbe, 'listening');
const address = portProbe.address();
const port = address.port;
await new Promise((resolve) => portProbe.close(resolve));

const child = spawn(binary, ['--listen', `127.0.0.1:${port}`, '--workers', '1'], {
    cwd: root,
    stdio: ['ignore', 'pipe', 'pipe'],
});

let serverOutput = '';
child.stdout.on('data', (chunk) => {
    serverOutput += chunk;
});
child.stderr.on('data', (chunk) => {
    serverOutput += chunk;
});

async function request(pathname) {
    return fetch(`http://127.0.0.1:${port}${pathname}`, { redirect: 'manual' });
}

async function waitUntilReady() {
    const deadline = Date.now() + 5_000;

    while (Date.now() < deadline) {
        if (child.exitCode !== null) {
            throw new Error(`Elephc server exited early (${child.exitCode}).\n${serverOutput}`);
        }

        try {
            const response = await request('/health');
            if (response.status === 200) {
                return;
            }
        } catch {
            // The listener may not be ready yet.
        }

        await new Promise((resolve) => setTimeout(resolve, 50));
    }

    throw new Error(`Timed out waiting for Elephc server.\n${serverOutput}`);
}

try {
    await waitUntilReady();

    const home = await request('/');
    assert.equal(home.status, 200);
    assert.match(home.headers.get('content-type'), /^text\/html/);
    assert.equal(home.headers.get('x-powered-by'), 'Tempest-on-Elephc');
    assert.match(await home.text(), /Tempest on <strong>Elephc<\/strong>/);
    console.log('ok / -> 200 HTML');

    const health = await request('/health');
    assert.equal(health.status, 200);
    assert.deepEqual(await health.json(), {
        status: 'ok',
        framework: 'tempest',
        runtime: 'elephc',
    });
    console.log('ok /health -> 200 JSON');

    const hello = await request('/hello/tempest');
    assert.equal(hello.status, 200);
    assert.equal(await hello.text(), 'Hello, tempest!\n');
    console.log('ok /hello/tempest -> 200 text');

    const redirect = await request('/elephc');
    assert.equal(redirect.status, 302);
    assert.equal(redirect.headers.get('location'), 'https://elephc.dev');
    console.log('ok /elephc -> 302');

    const missing = await request('/missing');
    assert.equal(missing.status, 404);
    assert.equal(await missing.text(), '404 Not Found\n');
    console.log('ok /missing -> 404');
} finally {
    if (child.exitCode === null) {
        child.kill('SIGINT');
        await Promise.race([
            once(child, 'exit'),
            new Promise((resolve) => setTimeout(resolve, 2_000)),
        ]);
    }
}
