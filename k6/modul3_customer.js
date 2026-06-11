import http from 'k6/http';
import { check, sleep, group } from 'k6';

export const options = {
  stages: [
    { duration: '30s', target: 50 },
    { duration: '30s', target: 100 },
    { duration: '30s', target: 200 },
    { duration: '30s', target: 2000 },
    { duration: '30s', target: 20000 },
    { duration: '1m', target: 0 },
  ],
  thresholds: {
    http_req_duration: ['p(95)<500'],
  },
};

const BASE_URL = 'http://laravel.test/api/v1';

export default function () {
  group('Modul 3: Autentikasi & Customer Features', function () {
    let loginPayload = JSON.stringify({
      email: 'customer@example.com',
      password: 'password'
    });
    
    let loginRes = http.post(`${BASE_URL}/auth/login`, loginPayload, {
      headers: { 'Content-Type': 'application/json' }
    });
    
    let token = '';
    if (loginRes.status === 200) {
        try {
            let body = JSON.parse(loginRes.body);
            token = body.token || body.access_token || (body.data && body.data.token) || '';
        } catch (e) {}
    }

    let authHeaders = { 
        headers: { 
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${token}` 
        } 
    };

    if (token) {
        let profileRes = http.get(`${BASE_URL}/customer/shipping-profile`, authHeaders);
        check(profileRes, { 'Get Shipping Profile status is 200': (r) => r.status === 200 });

        let calcPayload = JSON.stringify({
            origin_id: 1,
            destination_id: 2,
            weight: 5
        });
        let calcRes = http.post(`${BASE_URL}/customer/shipping-cost/calculate`, calcPayload, authHeaders);
        check(calcRes, { 'Calculate Shipping Cost status is 200': (r) => r.status === 200 });
    }
  });

  sleep(1);
}
