// k6 load test for the /live page (app/Livewire/LiveScoring.php).
//
// This is the page spectators keep open during an event — the front-end polls
// it every 3s via wire:poll.3s (see resources/views/livewire/live-scoring.blade.php).
// So the real load isn't "N page loads", it's "N browser tabs each firing a
// Livewire commit request every 3 seconds, indefinitely" — that's what this
// script simulates: each VU loads /live once, then repeats the same
// POST .../livewire-*/update request a real tab would send on every poll tick.
//
// Run from a machine OTHER than the server being tested (so the load
// generator's own CPU/network doesn't skew the target's numbers):
//
//   brew install k6            # or see https://k6.io/docs/get-started/installation
//   BASE_URL=http://192.168.1.19:8080 k6 run loadtest/live-scoring.js
//
// Tune concurrency/duration with env vars, e.g.:
//   BASE_URL=http://192.168.1.19:8080 VUS=100 DURATION=2m k6 run loadtest/live-scoring.js
//
// Optional: also mix in EventsList/EventDetail browsing traffic alongside the
// live viewers by setting EVENT_SLUG to a real event slug from the DB.

import http from 'k6/http';
import { check, sleep } from 'k6';
import { Trend, Rate } from 'k6/metrics';

const BASE_URL = __ENV.BASE_URL || 'http://127.0.0.1:8000';
const EVENT_SLUG = __ENV.EVENT_SLUG || '';
const VUS = Number(__ENV.VUS || 50);
const DURATION = __ENV.DURATION || '2m';
const POLL_INTERVAL_S = Number(__ENV.POLL_INTERVAL_S || 3); // matches wire:poll.3s

const pollDuration = new Trend('live_poll_duration');
const pollFailRate = new Rate('live_poll_failed');

export const options = {
    scenarios: {
        live_viewers: {
            executor: 'ramping-vus',
            exec: 'liveViewer',
            startVUs: 0,
            stages: [
                { duration: '20s', target: VUS },   // ramp up — people joining as the event starts
                { duration: DURATION, target: VUS }, // hold — steady crowd watching
                { duration: '15s', target: 0 },      // ramp down
            ],
            gracefulRampDown: '5s',
        },
        // Small background trickle of normal browsing, only if you pass EVENT_SLUG.
        ...(EVENT_SLUG ? {
            browsers: {
                executor: 'constant-vus',
                exec: 'browse',
                vus: Math.max(1, Math.round(VUS / 10)),
                duration: DURATION,
            },
        } : {}),
    },
    thresholds: {
        http_req_failed: ['rate<0.01'],
        live_poll_failed: ['rate<0.01'],
        // The whole point of the test: is the poll still fast under load?
        live_poll_duration: ['p(95)<800', 'p(99)<1500'],
    },
};

function extractLivewireBootstrap(html) {
    const snapshotMatch = html.match(/wire:snapshot="([^"]*)"/);
    const csrfMatch = html.match(/data-csrf="([^"]*)"/);
    const updateUriMatch = html.match(/data-update-uri="([^"]*)"/);
    if (!snapshotMatch || !csrfMatch || !updateUriMatch) {
        return null;
    }
    const decode = (s) => s
        .replace(/&quot;/g, '"')
        .replace(/&#039;/g, "'")
        .replace(/&amp;/g, '&')
        .replace(/&lt;/g, '<')
        .replace(/&gt;/g, '>');
    return {
        snapshot: decode(snapshotMatch[1]),
        csrf: csrfMatch[1],
        updateUri: updateUriMatch[1],
    };
}

export function liveViewer() {
    const res = http.get(`${BASE_URL}/live`);
    const ok = check(res, { 'GET /live is 200': (r) => r.status === 200 });
    if (!ok) return;

    const boot = extractLivewireBootstrap(res.body);
    if (!boot) {
        // Livewire markup shape changed, or the page errored without a 5xx — surface it.
        pollFailRate.add(1);
        return;
    }

    // Simulate the tab staying open, polling every POLL_INTERVAL_S like a real spectator would.
    const ticks = Math.max(1, Math.floor((Number(DURATION.replace(/[a-z]/g, '')) || 60) / POLL_INTERVAL_S));
    for (let i = 0; i < ticks; i++) {
        sleep(POLL_INTERVAL_S);

        const payload = JSON.stringify({
            _token: boot.csrf,
            components: [{ snapshot: boot.snapshot, updates: {}, calls: [] }],
        });

        const pollRes = http.post(boot.updateUri, payload, {
            headers: { 'Content-Type': 'application/json', 'X-Livewire': 'true' },
            tags: { name: 'live_poll' },
        });

        pollDuration.add(pollRes.timings.duration);
        const pollOk = check(pollRes, { 'poll is 200': (r) => r.status === 200 });
        pollFailRate.add(pollOk ? 0 : 1);
    }
}

export function browse() {
    http.get(`${BASE_URL}/events`);
    sleep(1);
    http.get(`${BASE_URL}/events/${EVENT_SLUG}`);
    sleep(Math.random() * 4 + 1);
}
