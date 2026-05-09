<?php

namespace App\Observers;

use App\Models\NgoDonor;
use App\Jobs\GeocodeAddressJob;

class NgoDonorObserver
{
    public function saved(NgoDonor $donor)
    {
        if ($donor->isDirty('address') && !empty($donor->address)) {
            GeocodeAddressJob::dispatch($donor);
        }
    }
}
