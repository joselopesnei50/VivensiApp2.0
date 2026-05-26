<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeocodingService
{
    /**
     * Geocode an address into latitude and longitude.
     * Priority: Google Maps (if key set) → BrasilAPI by CEP → Nominatim
     */
    public function geocode(string $address): ?array
    {
        $googleKey = SystemSetting::getValue('google_maps_api_key');

        if ($googleKey) {
            return $this->geocodeWithGoogle($address, $googleKey);
        }

        // Extract CEP from address string (e.g. "01310-100" or "01310100")
        if (preg_match('/\b(\d{5}-?\d{3})\b/', $address, $m)) {
            $coords = $this->geocodeWithBrasilApi($m[1]);
            if ($coords) return $coords;
        }

        return $this->geocodeWithNominatim($address);
    }

    private function geocodeWithBrasilApi(string $cep): ?array
    {
        $cep = preg_replace('/\D/', '', $cep);
        if (strlen($cep) !== 8) return null;

        try {
            $response = Http::timeout(8)->get("https://brasilapi.com.br/api/cep/v2/{$cep}");

            if ($response->successful()) {
                $data = $response->json();
                $lat = $data['location']['coordinates']['latitude']  ?? null;
                $lng = $data['location']['coordinates']['longitude'] ?? null;

                if ($lat && $lng) {
                    Log::info("BrasilAPI geocoding success for CEP {$cep}");
                    return ['lat' => (float) $lat, 'lng' => (float) $lng];
                }

                Log::warning("BrasilAPI CEP {$cep}: sem coordenadas na resposta.");
            }
        } catch (\Exception $e) {
            Log::warning("BrasilAPI geocoding error for CEP {$cep}: " . $e->getMessage());
        }

        return null;
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
            // Respect Nominatim rate limit of 1 request per second
            sleep(2);
            
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
            } else {
                Log::warning("Nominatim failed to find address: {$address}. Response: " . $response->body());
            }
        } catch (\Exception $e) {
            Log::error("Nominatim Geocoding Error: " . $e->getMessage());
        }

        return null;
    }
}
