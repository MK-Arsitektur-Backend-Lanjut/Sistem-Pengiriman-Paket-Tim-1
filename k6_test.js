import http from 'k6/http';
import { check, sleep, group } from 'k6';

export const options = {
  stages: [
    { duration: '30s', target: 50 },   // Pemanasan: 50 user
    { duration: '30s', target: 100 },  // Naik ke 100 user
    { duration: '30s', target: 200 },  // Naik ke 200 user
    { duration: '30s', target: 2000 },  // Naik ke 2000 user
    { duration: '30s', target: 20000 },  // Naik ke 20000 user
    
    { duration: '1m', target: 0 },     // Turun
  ],
  thresholds: {
    http_req_duration: ['p(95)<500'],
  },
};

const BASE_URL = 'http://laravel.test/api/v1'; // Use laravel.test within the sail network

export default function () {
  // ══════════════════════════════════════════════════════════════════
  // Modul 4: Fleet & Hub Monitoring (Public)
  // ══════════════════════════════════════════════════════════════════
  group('Modul 4: Fleet & Hub', function () {
    let hubRes = http.get(`${BASE_URL}/hub`);
    check(hubRes, { 'Get Hubs status is 200': (r) => r.status === 200 });

    let fleetRes = http.get(`${BASE_URL}/fleet`);
    check(fleetRes, { 'Get Fleets status is 200': (r) => r.status === 200 });
  });

  // ══════════════════════════════════════════════════════════════════
  // Modul 1: Warehouse & Sorting (Public)
  // ══════════════════════════════════════════════════════════════════
  group('Modul 1: Warehouse & Sorting', function () {
    let warehouseRes = http.get(`${BASE_URL}/warehouse`);
    check(warehouseRes, { 'Get Warehouses status is 200': (r) => r.status === 200 });

    let packageRes = http.get(`${BASE_URL}/package`);
    check(packageRes, { 'Get Packages status is 200': (r) => r.status === 200 });
  });

  // ══════════════════════════════════════════════════════════════════
  // Modul 2: Tracking System (Public)
  // ══════════════════════════════════════════════════════════════════
  group('Modul 2: Tracking System', function () {
    let trackingRes = http.get(`${BASE_URL}/tracking`);
    check(trackingRes, { 'Get Tracking status is 200': (r) => r.status === 200 });
  });

  // ══════════════════════════════════════════════════════════════════
  // Modul 3: Autentikasi, Kalkulator Ongkir & Profil (Protected)
  // ══════════════════════════════════════════════════════════════════
  group('Modul 3: Autentikasi & Customer Features', function () {
    // 1. Auth Login (Gunakan kredensial yang valid dari seeder agar berhasil)
    let loginPayload = JSON.stringify({
      email: 'customer@example.com', // Ubah dengan email seeder yang ada
      password: 'password'           // Ubah dengan password seeder
    });
    
    let loginRes = http.post(`${BASE_URL}/auth/login`, loginPayload, {
      headers: { 'Content-Type': 'application/json' }
    });
    
    // Mengekstrak token dari response login (asumsi token ada di r.body.token atau r.body.access_token)
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

    // Jika login berhasil dan token didapat, kita test endpoint protected
    if (token) {
        // 2. Profil Pengiriman
        let profileRes = http.get(`${BASE_URL}/customer/shipping-profile`, authHeaders);
        check(profileRes, { 'Get Shipping Profile status is 200': (r) => r.status === 200 });

        // 3. Kalkulator Ongkir Dinamis
        let calcPayload = JSON.stringify({
            origin_id: 1,      // Sesuaikan parameter kalkulator ongkir anda
            destination_id: 2, // Sesuaikan parameter kalkulator ongkir anda
            weight: 5          // Berat paket
        });
        let calcRes = http.post(`${BASE_URL}/customer/shipping-cost/calculate`, calcPayload, authHeaders);
        check(calcRes, { 'Calculate Shipping Cost status is 200': (r) => r.status === 200 });
    }
  });

  sleep(1);
}
