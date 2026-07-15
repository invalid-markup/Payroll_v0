<?php

namespace App\Observers;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditObserver
{
    public function created(Model $model): void
    {
        $this->log($model, 'created');
    }

    public function updated(Model $model): void
    {
        $this->log($model, 'updated');
    }

    public function deleted(Model $model): void
    {
        $this->log($model, 'deleted');
    }

    protected function log(Model $model, string $action): void
    {
        // Don't log if running from a console command without a logged-in user
        // However, we might still want system-level logs for CLI actions, but for MVP:
        $userId = Auth::id() ?? null;

        $oldValues = [];
        $newValues = [];

        if ($action === 'updated') {
            $oldValues = array_intersect_key($model->getOriginal(), $model->getDirty());
            $newValues = $model->getDirty();
        } elseif ($action === 'created') {
            $newValues = $model->getAttributes();
        } elseif ($action === 'deleted') {
            $oldValues = $model->getAttributes();
        }

        // Hide sensitive fields like password
        $hidden = $model->getHidden();
        foreach ($hidden as $hiddenField) {
            unset($oldValues[$hiddenField]);
            unset($newValues[$hiddenField]);
        }

        AuditLog::create([
            'user_id' => $userId,
            'audit_event_type' => $action,
            'model' => get_class($model),
            'model_id' => (string) $model->getKey(),
            'ip_address' => request()->ip(),
            'old_values' => empty($oldValues) ? null : $oldValues,
            'new_values' => empty($newValues) ? null : $newValues,
        ]);
    }
}
