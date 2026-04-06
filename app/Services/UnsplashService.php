<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UnsplashService
{
    protected $accessKey;
    protected $baseUrl = 'https://api.unsplash.com';

    public function __construct()
    {
        // Intentionally do not hit the database here.
    }

    protected function resolveConfig()
    {
        if ($this->accessKey) return;
        $this->accessKey = SystemSetting::getValue('unsplash_access_key');
    }

    /**
     * Search for photos on Unsplash
     * @param string $query
     * @param int $perPage
     * @return array
     */
    public function searchPhotos($query, $perPage = 3)
    {
        $this->resolveConfig();

        if (!$this->accessKey) {
            Log::warning('Unsplash Access Key not configured.');
            return [];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Client-ID ' . $this->accessKey
            ])->get($this->baseUrl . '/search/photos', [
                'query' => $query,
                'per_page' => $perPage,
                'orientation' => 'landscape'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return collect($data['results'])->map(function ($photo) {
                    return [
                        'id' => $photo['id'],
                        'url_small' => $photo['urls']['small'],
                        'url_regular' => $photo['urls']['regular'],
                        'photographer' => $photo['user']['name'],
                        'photographer_url' => $photo['user']['links']['html'],
                    ];
                })->toArray();
            }

            Log::error('Unsplash API Error: ' . $response->body());
            return [];

        } catch (\Exception $e) {
            Log::error('Unsplash Connection Error: ' . $e->getMessage());
            return [];
        }
    }
}
