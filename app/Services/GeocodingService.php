<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeocodingService
{
    /**
     * Geocode an address into latitude and longitude.
     */
    public function geocode(string $address): ?array
    {
        $googleKey = SystemSetting::getValue('google_maps_api_key');

        if ($googleKey) {
            return $this->geocodeWithGoogle($address, $googleKey);
        }

        return $this->geocodeWithNominatim($address);
    }

    private function geocodeWithGoogle(string $address, string $key): ?array
    {
        try {
            $response = Http::get('https://maps.googleapis.com/maps/api/geocode/json', [
                'address' => $address,
                'key' => $key,
            ]);

            if ($response->successful() && isset($response['results'][0])) {
                $location = $response['results'][0]['geometry']['location'];
                return [
                    'lat' => $location['lat'],
                    'lng' => $location['lng'],
                ];
            }
        } catch (\Exception $e) {
            Log::error("Google Geocoding Error: " . $e->getMessage());
        }

        return null;
    }

    private function geocodeWithNominatim(string $address): ?array
    {
        try {
            // Nominatim requires a User-Agent
            $response = Http::withHeaders([
                'User-Agent' => 'VivensiApp/1.0 (contact@vivensi.com)',
            ])->get('https://nominatim.openstreetmap.org/search', [
                'q' => $address,
                'format' => 'json',
                'limit' => 1,
            ]);

            if ($response->successful() && isset($response[0])) {
                return [
                    'lat' => (float) $response[0]['lat'],
                    'lng' => (float) $response[0]['lon'],
                ];
            }
        } catch (\Exception $e) {
            Log::error("Nominatim Geocoding Error: " . $e->getMessage());
        }

        return null;
    }
}
