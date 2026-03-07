<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\NgoGrant;
use App\Models\Project;

class LinkExistingGrantsToProjects extends Seeder
{
    public function run()
    {
        $grants = NgoGrant::all();
        foreach ($grants as $grant) {
            if (!$grant->project) {
                Project::create([
                    'tenant_id' => $grant->tenant_id,
                    'name' => $grant->title,
                    'description' => $grant->notes,
                    'budget' => $grant->value,
                    'start_date' => $grant->start_date,
                    'end_date' => $grant->deadline,
                    'status' => 'active',
                    'ngo_grant_id' => $grant->id,
                ]);
                echo "Linked Grant ID {$grant->id} to a new Project.\n";
            }
        }
    }
}
