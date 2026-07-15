<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CostCenter extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'cost_centers';

    protected $fillable = [
        'code',
        'name',
        'branch_id',
        'department_id',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }
}
