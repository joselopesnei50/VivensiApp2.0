<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\GeocodingService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class GeocodeAddressJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public int $timeout = 60;

    protected $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    public function handle(GeocodingService $geocodingService)
    {
        if (empty($this->model->address) && empty($this->model->address_city)) {
            return;
        }

        $coords = null;

        // Use structured geocoding when model has address parts (more accurate)
        if (!empty($this->model->address_city)) {
            $coords = $geocodingService->geocodeStructured(
                (string) ($this->model->address_zip   ?? ''),
                (string) ($this->model->address_street ?? ''),
                (string) ($this->model->address_city  ?? ''),
                (string) ($this->model->address_state ?? '')
            );
        }

        // Fallback: free-text geocoding from the composed address field
        if (!$coords && !empty($this->model->address)) {
            $coords = $geocodingService->geocode($this->model->address);
        }

        if ($coords) {
            $this->model->update([
                'latitude'  => $coords['lat'],
                'longitude' => $coords['lng'],
            ]);
            Log::info("Geocoding success for {$this->model->getTable()} ID {$this->model->id}: {$coords['lat']},{$coords['lng']}");
        } else {
            Log::warning("Geocoding failed for {$this->model->getTable()} ID {$this->model->id}");
        }
    }
}
