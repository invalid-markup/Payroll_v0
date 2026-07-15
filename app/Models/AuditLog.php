<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasUuids;

    protected $table = 'audit_logs';

    protected $fillable = [
        'user_id',
        'audit_event_type',
        'model',
        'model_id',
        'ip_address',
        'old_values',
        'new_values',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Prevent deletion of Audit Logs to enforce Hard Record / Append-Only policy.
     */
    public function delete()
    {
        throw new \Exception('Audit logs are immutable and cannot be deleted.');
    }
}
