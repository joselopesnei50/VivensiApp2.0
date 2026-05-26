<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeocodingService
{
    public function geocode(string $address): ?array
    {
        $googleKey = SystemSetting::getValue('google_maps_api_key');
        if ($googleKey) {
            return $this->geocodeWithGoogle($address, $googleKey);
        }

        // Extract CEP from address string and try BrasilAPI
        if (preg_match('/\b(\d{5}-?\d{3})\b/', $address, $m)) {
            $coords = $this->geocodeWithBrasilApi($m[1]);
            if ($coords) return $coords;
        }

        return $this->geocodeWithNominatim($address);
    }

    /**
     * Geocode using structured address parts — more accurate than free text.
     * Called by GeocodeAddressJob when model has structured fields.
     */
    public function geocodeStructured(string $zip, string $street, string $city, string $state): ?array
    {
        $googleKey = SystemSetting::getValue('google_maps_api_key');
        if ($googleKey) {
            $composed = implode(', ', array_filter([$street, $city, $state, 'Brasil']));
            return $this->geocodeWithGoogle($composed, $googleKey);
        }

        // 1. BrasilAPI by CEP
        if ($zip) {
            $coords = $this->geocodeWithBrasilApi($zip);
            if ($coords) return $coords;
        }

        // 2. Nominatim structured query (much better than free text for Brazilian addresses)
        if ($city) {
            $coords = $this->geocodeWithNominatimStructured($street, $city, $state);
            if ($coords) return $coords;
        }

        return null;
    }

    private function geocodeWithGoogle(string $address, string $key): ?array
    {
        try {
            $response = Http::timeout(10)->get('https://maps.googleapis.com/maps/api/geocode/json', [
                'address' => $address,
                'key'     => $key,
            ]);

            if ($response->successful() && isset($response['results'][0])) {
                $location = $response['results'][0]['geometry']['location'];
                return ['lat' => $location['lat'], 'lng' => $location['lng']];
            }
        } catch (\Exception $e) {
            Log::error("Google Geocoding Error: " . $e->getMessage());
        }

        return null;
    }

    private function geocodeWithBrasilApi(string $cep): ?array
    {
        $cep = preg_replace('/\D/', '', $cep);
        if (strlen($cep) !== 8) return null;

        try {
            $response = Http::timeout(8)->get("https://brasilapi.com.br/api/cep/v2/{$cep}");

            if ($response->successful()) {
                $data = $response->json();
                $lat  = $data['location']['coordinates']['latitude']  ?? null;
                $lng  = $data['location']['coordinates']['longitude'] ?? null;

                if ($lat && $lng) {
                    Log::info("BrasilAPI geocoding success for CEP {$cep}");
                    return ['lat' => (float) $lat, 'lng' => (float) $lng];
                }
            }
        } catch (\Exception $e) {
            Log::warning("BrasilAPI geocoding error for CEP {$cep}: " . $e->getMessage());
        }

        return null;
    }

    private function geocodeWithNominatimStructured(string $street, string $city, string $state): ?array
    {
        try {
            sleep(1);

            $params = array_filter([
                'format'  => 'json',
                'limit'   => 1,
                'street'  => $street ?: null,
                'city'    => $city,
                'state'   => $state ?: null,
                'country' => 'Brazil',
            ]);

            $response = Http::timeout(10)->withHeaders([
                'User-Agent' => 'VivensiApp/1.0 (contato@vivensi.com.br)',
            ])->get('https://nominatim.openstreetmap.org/search', $params);

            if ($response->successful() && isset($response->json()[0])) {
                $r = $response->json()[0];
                Log::info("Nominatim structured geocoding success for {$city}/{$state}");
                return ['lat' => (float) $r['lat'], 'lng' => (float) $r['lon']];
            }

            Log::warning("Nominatim structured: sem resultado para {$city}/{$state}");
        } catch (\Exception $e) {
            Log::error("Nominatim structured error: " . $e->getMessage());
        }

        return null;
    }

    private function geocodeWithNominatim(string $address): ?array
    {
        try {
            sleep(2);

            $response = Http::timeout(10)->withHeaders([
                'User-Agent' => 'VivensiApp/1.0 (contato@vivensi.com.br)',
            ])->get('https://nominatim.openstreetmap.org/search', [
                'q'      => $address,
                'format' => 'json',
                'limit'  => 1,
            ]);

            if ($response->successful() && isset($response->json()[0])) {
                $r = $response->json()[0];
                return ['lat' => (float) $r['lat'], 'lng' => (float) $r['lon']];
            }

            Log::warning("Nominatim free-text: sem resultado para: {$address}");
        } catch (\Exception $e) {
            Log::error("Nominatim Geocoding Error: " . $e->getMessage());
        }

        return null;
    }
}
