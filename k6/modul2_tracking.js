/**
 * K6 Load Testing - Module 2: Tracking System
 * Purpose: Testing the performance of the tracking API endpoint
 * Tests ramp-up, sustain, and cooldown scenarios with performance thresholds
 */

import http from 'k6/http';
import { check, sleep, group } from 'k6';

// ==================== CONFIGURATION ====================

/**
 * Load testing stages configuration
 * - Ramp up: 50 -> 100 -> 200 -> 2000 users
 * - Cooldown: 1 minute to 0 users
 */
const LOAD_STAGES = [
  { duration: '30s', target: 50 },
  { duration: '30s', target: 100 },
  { duration: '30s', target: 200 },
  { duration: '30s', target: 2000 },
  { duration: '1m', target: 0 },
];

/**
 * Performance thresholds for test validation
 * - HTTP request duration: 95th percentile must be < 500ms
 */
const PERFORMANCE_THRESHOLDS = {
  http_req_duration: ['p(95)<500'],
};

/**
 * Test options configuration
 */
export const options = {
  stages: LOAD_STAGES,
  thresholds: PERFORMANCE_THRESHOLDS,
};

// ==================== CONSTANTS ====================

const BASE_URL = 'http://laravel.test/api/v1';
const API_TIMEOUT = 10000; // 10 seconds
const REQUEST_DELAY = 1; // 1 second between requests

// ==================== HELPER FUNCTIONS ====================

/**
 * Perform a GET request to the tracking endpoint
 * @returns {object} HTTP response object
 */
function getTrackingData() {
  const endpoint = `${BASE_URL}/tracking`;
  return http.get(endpoint);
}

/**
 * Validate tracking API response
 * @param {object} response - HTTP response from tracking endpoint
 * @returns {boolean} True if all checks pass
 */
function validateTrackingResponse(response) {
  return check(response, {
    'Get Tracking status is 200': (r) => r.status === 200,
    'Response time < 500ms': (r) => r.timings.duration < 500,
  });
}

// ==================== MAIN TEST FUNCTION ====================

/**
 * Main test execution function
 * Runs for each virtual user in the load test
 */
export default function () {
  group('Modul 2: Tracking System', function () {
    // Fetch tracking data
    const trackingResponse = getTrackingData();

    // Validate response
    validateTrackingResponse(trackingResponse);
  });

  // Wait before next iteration
  sleep(REQUEST_DELAY);
}
