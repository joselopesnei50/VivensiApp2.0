<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Models\Prospect;
use App\Models\SystemSetting;

class LeadSearchService {
    public function search($term, $location, $tenantId) {
        $apiKey = SystemSetting::getValue('serper_api_key');

        if (!$apiKey) {
            throw new \Exception('Chave da API Serper não configurada.');
        }

        $response = Http::withHeaders(['X-API-KEY' => $apiKey])
            ->timeout(30)
            ->post('https://google.serper.dev/maps', [
                'q' => "$term em $location",
                'gl' => 'br', 
                'hl' => 'pt-br'
            ]);

        if ($response->failed()) {
            throw new \Exception('Erro na busca do Serper: ' . $response->body());
        }

        $results = $response->json();
        $prospectsCreated = 0;

        if (isset($results['places'])) {
            foreach ($results['places'] as $item) {
                Prospect::updateOrCreate(
                    [
                        'company_name' => $item['title'],
                        'tenant_id' => $tenantId
                    ],
                    [
                        'phone' => $item['phoneNumber'] ?? null,
                        'address' => $item['address'] ?? null,
                        'website' => $item['website'] ?? null,
                        'google_rating' => $item['rating'] ?? 0,
                        'total_reviews' => $item['ratingCount'] ?? 0,
                        'category' => $term,
                        'status' => 'raw'
                    ]
                );
                $prospectsCreated++;
            }
        }

        return $prospectsCreated;
    }
}
