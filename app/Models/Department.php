<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'departments';

    protected $fillable = ['code', 'name', 'branch_id'];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }
}
