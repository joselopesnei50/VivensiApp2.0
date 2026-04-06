<?php

namespace App\Observers;

use App\Models\Project;
use App\Jobs\GeocodeAddressJob;

class ProjectObserver
{
    public function saved(Project $project)
    {
        if ($project->isDirty('address') && !empty($project->address)) {
            GeocodeAddressJob::dispatch($project);
        }
    }
}
