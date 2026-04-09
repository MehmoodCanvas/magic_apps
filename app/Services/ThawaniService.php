<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ThawaniService
{
    protected $secretKey;
    protected $publishableKey;
    protected $baseUrl;
    protected $checkoutUrl;

    public function __construct()
    {
        $this->secretKey = config('services.thawani.secret_key');
        $this->publishableKey = config('services.thawani.publishable_key');
        $this->baseUrl = config('services.thawani.base_url');
        $this->checkoutUrl = config('services.thawani.checkout_url');
    }

    /**
     * Create a checkout session.
     *
     * @param array $data
     * @return array
     */
    public function createCheckoutSession(array $data)
    {
        if (empty($this->secretKey)) {
            return [
                'status' => false,
                'message' => 'Thawani Secret Key is missing in configuration. Make sure THAWANI_SECRET_KEY is set in .env and you have run php artisan config:clear.'
            ];
        }

        try {
            $response = Http::withHeaders([
                'thawani-api-key' => $this->secretKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/checkout/session', [
                'client_reference_id' => $data['client_reference_id'],
                'products' => $data['products'],
                'success_url' => $data['success_url'],
                'cancel_url' => $data['cancel_url'],
                'metadata' => $data['metadata'] ?? [],
            ]);

            if ($response->successful()) {
                $sessionData = $response->json();
                $sessionId = $sessionData['data']['session_id'];
                
                return [
                    'status' => true,
                    'session_id' => $sessionId,
                    'redirect_url' => $this->checkoutUrl . '/' . $sessionId . '?key=' . $this->publishableKey,
                    'data' => $sessionData
                ];
            }

            Log::error('Thawani Session Creation Failed', [
                'response' => $response->json(),
                'status' => $response->status()
            ]);

            return [
                'status' => false,
                'message' => $response->json()['description'] ?? 'Failed to create Thawani session',
                'errors' => $response->json()
            ];

        } catch (\Exception $e) {
            Log::error('Thawani Service Error: ' . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Something went wrong with Thawani service: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get session details to verify payment.
     *
     * @param string $sessionId
     * @return array
     */
    public function getSession(string $sessionId)
    {
        try {
            $response = Http::withHeaders([
                'thawani-api-key' => $this->secretKey,
            ])->get($this->baseUrl . '/checkout/session/' . $sessionId);

            if ($response->successful()) {
                return [
                    'status' => true,
                    'data' => $response->json()['data']
                ];
            }

            return [
                'status' => false,
                'message' => $response->json()['description'] ?? 'Failed to retrieve Thawani session'
            ];

        } catch (\Exception $e) {
            Log::error('Thawani Get Session Error: ' . $e->getMessage());
            return [
                'status' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}
