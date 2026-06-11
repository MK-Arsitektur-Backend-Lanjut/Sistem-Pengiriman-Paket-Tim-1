import http from 'k6/http';
import { check, sleep, group } from 'k6';

export const options = {
  stages: [
    { duration: '30s', target: 50 },
    // { duration: '30s', target: 100 },
    // { duration: '30s', target: 200 },
    // { duration: '30s', target: 2000 },
    // { duration: '30s', target: 20000 },
    { duration: '1m', target: 0 },
  ],
  thresholds: {
    http_req_duration: ['p(95)<500'],
  },
};

const BASE_URL = 'http://laravel.test/api/v1';

export default function () {
  group('Modul 4: Fleet & Hub', function () {
    let hubRes = http.get(`${BASE_URL}/hub`);
    check(hubRes, { 'Get Hubs status is 200': (r) => r.status === 200 });

    let fleetRes = http.get(`${BASE_URL}/fleet`);
    check(fleetRes, { 'Get Fleets status is 200': (r) => r.status === 200 });
  });

  sleep(1);
}
