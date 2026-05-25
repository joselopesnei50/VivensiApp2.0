<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class FailedJobsController extends Controller
{
    public function index()
    {
        $jobs = DB::table('failed_jobs')
            ->orderByDesc('failed_at')
            ->paginate(25);

        // Decodifica o displayName do payload para exibir na tabela
        $jobs->getCollection()->transform(function ($job) {
            $payload        = json_decode($job->payload, true);
            $job->job_class = data_get($payload, 'displayName', 'N/A');
            $job->attempts  = data_get($payload, 'attempts', '?');
            return $job;
        });

        return view('admin.failed-jobs.index', compact('jobs'));
    }

    public function retry(string $uuid)
    {
        $exists = DB::table('failed_jobs')->where('uuid', $uuid)->exists();
        if (!$exists) {
            return back()->with('error', 'Job não encontrado.');
        }

        Artisan::call('queue:retry', ['id' => [$uuid]]);

        return back()->with('success', "Job {$uuid} reenfileirado.");
    }

    public function retryAll()
    {
        $count = DB::table('failed_jobs')->count();
        if ($count === 0) {
            return back()->with('info', 'Nenhum job falhado para retentar.');
        }

        Artisan::call('queue:retry', ['id' => ['all']]);

        return back()->with('success', "{$count} job(s) reenfileirado(s).");
    }

    public function destroy(string $uuid)
    {
        DB::table('failed_jobs')->where('uuid', $uuid)->delete();

        return back()->with('success', 'Job removido.');
    }

    public function flush()
    {
        $count = DB::table('failed_jobs')->count();
        DB::table('failed_jobs')->delete();

        // Zera o contador de alerta
        \Illuminate\Support\Facades\Cache::forget('queue.failed_jobs.last_count');

        return back()->with('success', "{$count} job(s) removido(s).");
    }
}
