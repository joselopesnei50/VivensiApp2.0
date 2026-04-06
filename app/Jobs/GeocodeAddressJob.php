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

    protected $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    public function handle(GeocodingService $geocodingService)
    {
        if (empty($this->model->address)) {
            return;
        }

        $coords = $geocodingService->geocode($this->model->address);

        if ($coords) {
            $this->model->update([
                'latitude' => $coords['lat'],
                'longitude' => $coords['lng'],
            ]);
            Log::info("Geocoding success for {$this->model->getTable()} ID: {$this->model->id}");
        } else {
            Log::warning("Geocoding failed for {$this->model->getTable()} ID: {$this->model->id}");
        }
    }
}
