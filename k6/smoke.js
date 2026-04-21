import http from 'k6/http';
import { check, sleep } from 'k6';

export const options = {
    vus: 10,
    duration: '30s',
    thresholds: {
        http_req_failed: ['rate<0.01'],
        http_req_duration: ['p(95)<500'],
    },
};

const BASE_URL = __ENV.BASE_URL || 'http://devkit-lti1p3.localhost';
const API_KEY = __ENV.API_KEY || 'dcf8cb90ac4db043f33e29d2419d93f0';

const apiHeaders = {
    headers: {
        Authorization: `Bearer ${API_KEY}`,
        Accept: 'application/json',
    },
};

export default function () {
    const health = http.get(`${BASE_URL}/health-check`);
    check(health, {
        'health 200': (r) => r.status === 200,
    });

    const dashboard = http.get(`${BASE_URL}/`);
    check(dashboard, {
        'dashboard 200': (r) => r.status === 200,
    });

    const platforms = http.get(`${BASE_URL}/api/platforms`, apiHeaders);
    check(platforms, {
        'api/platforms 200': (r) => r.status === 200,
    });

    const tools = http.get(`${BASE_URL}/api/tools`, apiHeaders);
    check(tools, {
        'api/tools 200': (r) => r.status === 200,
    });

    sleep(1);
}
