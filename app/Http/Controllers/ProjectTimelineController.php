<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectTimelineRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProjectTimelineController extends Controller
{
    public function store(Request $request, $projectId)
    {
        abort_unless(
            in_array(auth()->user()->role, ['manager', 'super_admin', 'ngo'], true),
            403
        );

        $tenantId = auth()->user()->tenant_id;
        $project  = Project::where('id', $projectId)->where('tenant_id', $tenantId)->firstOrFail();

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'type' => 'required|in:milestone,photo,video,status',
            'date' => 'nullable|date',
            'media' => 'nullable|image|max:10240', // 10MB limit for photos
            'external_url' => 'nullable|url|max:2048',
        ]);

        $data = $validated;
        $data['project_id'] = $project->id;
        $data['tenant_id'] = $project->tenant_id;
        $data['date'] = $validated['date'] ?? now();

        if ($request->hasFile('media')) {
            $path = $request->file('media')->store('projects/timeline', 'public');
            $data['media_path'] = $path;
        }

        ProjectTimelineRecord::create($data);

        return redirect()->back()->with('success', 'Registro de impacto adicionado com sucesso!');
    }

    public function destroy($projectId, $id)
    {
        abort_unless(
            in_array(auth()->user()->role, ['manager', 'super_admin', 'ngo'], true),
            403
        );

        $tenantId = auth()->user()->tenant_id;
        $record   = ProjectTimelineRecord::where('project_id', $projectId)
            ->where('tenant_id', $tenantId)
            ->findOrFail($id);

        if ($record->media_path) {
            Storage::disk('public')->delete($record->media_path);
        }

        $record->delete();

        return redirect()->back()->with('success', 'Registro de impacto removido!');
    }
}
