import http from 'k6/http';
import { check, sleep, group } from 'k6';

const BASE_URL = 'http://laravel.test/api/v1';

const ENDPOINTS = {
  warehouse: `${BASE_URL}/warehouse`,
  package: `${BASE_URL}/package`,
};

export const options = {
  stages: [
    { duration: '30s', target: 50 }, 
    { duration: '30s', target: 100 },
    // { duration: '30s', target: 200 },
    // { duration: '30s', target: 2000 },
    // { duration: '30s', target: 20000 }, 
    { duration: '1m', target: 0 },
  ],
  thresholds: {
    http_req_duration: ['p(95)<500'],
  },
};

function getWarehouses() {
  const res = http.get(ENDPOINTS.warehouse);
  check(res, { 'Get Warehouses status is 200': (r) => r.status === 200 });
}

function getPackages() {
  const res = http.get(ENDPOINTS.package);
  check(res, { 'Get Packages status is 200': (r) => r.status === 200 });
}

export default function () {
  group('Modul 1: Warehouse & Sorting', function () {
    getWarehouses();
    getPackages();
  });

  sleep(1);
}
