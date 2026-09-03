<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class FcmService
{
    /**
     * Send FCM push notification to a specific device token.
     */
    public static function sendNotification(string $fcmToken, string $title, string $body, array $data = []): bool
    {
        try {
            $accessToken = self::getAccessToken();

            if (!$accessToken) {
                Log::error('FCM: Failed to get access token');
                return false;
            }

            $projectId = config('services.firebase.project_id');

            $message = [
                'message' => [
                    'token' => $fcmToken,
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                    ],
                    'data' => array_map('strval', $data), // FCM data must be string values
                ],
            ];

            $response = Http::withToken($accessToken)
                ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", $message);

            if ($response->successful()) {
                Log::info('FCM: Notification sent successfully', ['token' => substr($fcmToken, 0, 20) . '...']);
                return true;
            }

            Log::error('FCM: Failed to send notification', [
                'status' => $response->status(),
                'error' => $response->json(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('FCM: Exception while sending notification', [
                'message' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get OAuth2 access token using service account credentials.
     * Tokens are cached for 55 minutes (they expire after 60).
     */
    private static function getAccessToken(): ?string
    {
        return Cache::remember('fcm_access_token', 3300, function () {
            $credentialsPath = base_path(config('services.firebase.credentials'));

            if (!file_exists($credentialsPath)) {
                Log::error('FCM: Credentials file not found at ' . $credentialsPath);
                return null;
            }

            $credentials = json_decode(file_get_contents($credentialsPath), true);

            // Create JWT token
            $now = time();
            $header = self::base64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
            $claims = self::base64UrlEncode(json_encode([
                'iss' => $credentials['client_email'],
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => $credentials['token_uri'],
                'iat' => $now,
                'exp' => $now + 3600,
            ]));

            $signature = '';
            $dataToSign = $header . '.' . $claims;
            openssl_sign($dataToSign, $signature, $credentials['private_key'], OPENSSL_ALGO_SHA256);
            $jwt = $dataToSign . '.' . self::base64UrlEncode($signature);

            // Exchange JWT for access token
            $response = Http::asForm()->post($credentials['token_uri'], [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);

            if ($response->successful()) {
                return $response->json('access_token');
            }

            Log::error('FCM: Failed to get access token', ['error' => $response->json()]);
            return null;
        });
    }

    /**
     * Base64 URL-safe encoding (required for JWT).
     */
    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
