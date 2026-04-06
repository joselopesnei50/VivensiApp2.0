<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectHealthHistory;
use App\Models\Transaction;
use App\Models\Task;
use App\Models\ProjectMember;
use App\Mail\HealthRadarAlertMail;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class ProjectHealthService
{
    /**
     * Calculate and store health scores for a project.
     */
    public function recordSnapshot(Project $project)
    {
        $financial = $this->calculateFinancialScore($project);
        $execution = $this->calculateExecutionScore($project);
        $team = $this->calculateTeamScore($project);
        $compliance = 100; // Placeholder for future compliance logic

        $overall = (int) (($financial + $execution + $team + $compliance) / 4);

        $lastSnapshot = ProjectHealthHistory::where('project_id', $project->id)
            ->orderBy('recorded_at', 'desc')
            ->first();

        $history = ProjectHealthHistory::create([
            'project_id' => $project->id,
            'tenant_id' => $project->tenant_id,
            'financial_score' => $financial,
            'execution_score' => $execution,
            'team_score' => $team,
            'compliance_score' => $compliance,
            'overall_score' => $overall,
            'recorded_at' => Carbon::now(),
        ]);

        if ($lastSnapshot && ($lastSnapshot->overall_score - $overall) >= 20) {
            $this->sendAlert($project, $overall, $lastSnapshot->overall_score);
        }

        return $history;
    }

    private function calculateFinancialScore(Project $project)
    {
        $totalSpent = Transaction::where('project_id', $project->id)
            ->where('type', 'expense')
            ->sum('amount');

        if ($project->budget <= 0) return 100;

        $usageRatio = ($totalSpent / $project->budget) * 100;

        // Ideal usage is around 50-80% depending on time. 
        // Simple logic: if > 100%, score drops linearly.
        if ($usageRatio > 100) {
            return max(0, 100 - ($usageRatio - 100));
        }

        return 100;
    }

    private function calculateExecutionScore(Project $project)
    {
        $totalTasks = Task::where('project_id', $project->id)->count();
        if ($totalTasks === 0) return 100;

        $completedTasks = Task::where('project_id', $project->id)
            ->where('status', 'completed')
            ->count();

        return (int) (($completedTasks / $totalTasks) * 100);
    }

    private function calculateTeamScore(Project $project)
    {
        $memberCount = $project->members()->count();
        
        // 1 point for each member up to 10
        return min(100, $memberCount * 20);
    }

    private function sendAlert(Project $project, $currentScore, $previousScore)
    {
        // Get project manager(s) or tenant owner
        $recipients = $project->tenant->users()->whereIn('role', ['manager', 'admin'])->get();

        foreach ($recipients as $recipient) {
            Mail::to($recipient->email)->send(new HealthRadarAlertMail($project, $currentScore, $previousScore));
        }
    }
}
