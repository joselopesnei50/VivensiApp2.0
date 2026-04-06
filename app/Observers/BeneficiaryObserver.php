<?php

namespace App\Observers;

use App\Models\Beneficiary;
use App\Jobs\GeocodeAddressJob;

class BeneficiaryObserver
{
    public function saved(Beneficiary $beneficiary)
    {
        if ($beneficiary->isDirty('address') && !empty($beneficiary->address)) {
            GeocodeAddressJob::dispatch($beneficiary);
        }
    }
}
