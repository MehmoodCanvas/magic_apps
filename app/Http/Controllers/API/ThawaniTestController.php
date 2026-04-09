<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\ThawaniService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ThawaniTestController extends Controller
{
    protected $thawaniService;

    public function __construct(ThawaniService $thawaniService)
    {
        $this->thawaniService = $thawaniService;
    }

    /**
     * Debug endpoint - shows config and tests raw API call.
     */
    public function debugCheck()
    {
        $secretKey = config('services.thawani.secret_key');
        $publishableKey = config('services.thawani.publishable_key');
        $baseUrl = config('services.thawani.base_url');
        $mode = config('services.thawani.mode');

        // Raw test with official Thawani UAT demo key first
        $demoSecretKey = 'rRQ26GcsZzoEhbrP2HZvLYDbn9C9et';

        // Try with your key
        $yourKeyResponse = null;
        $yourKeyError = null;
        try {
            $yourKeyResponse = Http::withHeaders([
                'thawani-api-key' => $secretKey,
                'Content-Type' => 'application/json',
            ])->post($baseUrl . '/checkout/session', [
                'client_reference_id' => 'debug-test-' . time(),
                'products' => [
                    [
                        'name' => 'Debug Test',
                        'quantity' => 1,
                        'unit_amount' => 1000,
                    ]
                ],
                'success_url' => url('/api/thawani-test/success'),
                'cancel_url' => url('/api/thawani-test/cancel'),
                'metadata' => [
                    'test' => true
                ]
            ]);
        } catch (\Exception $e) {
            $yourKeyError = $e->getMessage();
        }

        // Try with demo key
        $demoKeyResponse = null;
        $demoKeyError = null;
        try {
            $demoKeyResponse = Http::withHeaders([
                'thawani-api-key' => $demoSecretKey,
                'Content-Type' => 'application/json',
            ])->post('https://uatcheckout.thawani.om/api/v1/checkout/session', [
                'client_reference_id' => 'demo-test-' . time(),
                'products' => [
                    [
                        'name' => 'Demo Test',
                        'quantity' => 1,
                        'unit_amount' => 1000,
                    ]
                ],
                'success_url' => url('/api/thawani-test/success'),
                'cancel_url' => url('/api/thawani-test/cancel'),
                'metadata' => [
                    'test' => true
                ]
            ]);
        } catch (\Exception $e) {
            $demoKeyError = $e->getMessage();
        }

        return response()->json([
            'debug_info' => [
                'mode' => $mode,
                'base_url' => $baseUrl,
                'secret_key_length' => strlen($secretKey ?? ''),
                'secret_key_first_5' => substr($secretKey ?? '', 0, 5),
                'secret_key_last_5' => substr($secretKey ?? '', -5),
                'publishable_key_length' => strlen($publishableKey ?? ''),
            ],
            'your_key_test' => [
                'status_code' => $yourKeyResponse ? $yourKeyResponse->status() : 'error',
                'response' => $yourKeyResponse ? $yourKeyResponse->json() : $yourKeyError,
            ],
            'demo_key_test' => [
                'status_code' => $demoKeyResponse ? $demoKeyResponse->status() : 'error',
                'response' => $demoKeyResponse ? $demoKeyResponse->json() : $demoKeyError,
            ],
        ]);
    }

    /**
     * Create a test checkout session.
     */
    public function testCheckout()
    {
        $data = [
            'client_reference_id' => 'test-order-' . time(),
            'products' => [
                [
                    'name' => 'Test Coaching Session',
                    'quantity' => 1,
                    'unit_amount' => 5000,
                ]
            ],
            'success_url' => url('/api/thawani-test/success'),
            'cancel_url' => url('/api/thawani-test/cancel'),
            'metadata' => [
                'test_mode' => true,
                'customer_name' => 'Test User'
            ]
        ];

        $result = $this->thawaniService->createCheckoutSession($data);

        if ($result['status']) {
            return response()->json([
                'status' => true,
                'message' => 'Checkout session created successfully.',
                'redirect_url' => $result['redirect_url'],
                'session_id' => $result['session_id']
            ]);
        }

        return response()->json([
            'status' => false,
            'message' => $result['message'],
            'errors' => $result['errors'] ?? []
        ], 400);
    }

    /**
     * Handle successful payment callback.
     */
    public function success(Request $request)
    {
        $sessionId = $request->query('session_id');

        if (!$sessionId) {
            return response()->json([
                'status' => true,
                'message' => 'Payment completed successfully (no session_id to verify).',
            ]);
        }

        $result = $this->thawaniService->getSession($sessionId);

        return response()->json([
            'status' => true,
            'message' => 'Payment successful (Verified via Thawani).',
            'session_details' => $result['data'] ?? []
        ]);
    }

    /**
     * Handle cancelled payment callback.
     */
    public function cancel()
    {
        return response()->json([
            'status' => false,
            'message' => 'Payment was cancelled by the user.'
        ]);
    }
}
