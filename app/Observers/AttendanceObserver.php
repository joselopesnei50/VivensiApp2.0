<?php

namespace App\Observers;

use App\Jobs\RecalcularConformidadeJob;
use App\Models\Attendance;

class AttendanceObserver
{
    public function created(Attendance $attendance): void
    {
        $this->dispatch($attendance);
    }

    public function updated(Attendance $attendance): void
    {
        if ($attendance->wasChanged(['gratuito', 'tipificacao_suas'])) {
            $this->dispatch($attendance);
        }
    }

    public function deleted(Attendance $attendance): void
    {
        $this->dispatch($attendance);
    }

    private function dispatch(Attendance $attendance): void
    {
        if ($attendance->tenant_id) {
            RecalcularConformidadeJob::dispatch($attendance->tenant_id);
        }
    }
}
