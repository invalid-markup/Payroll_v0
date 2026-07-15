<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Branch extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'branches';

    protected $fillable = ['code', 'name'];

    public function departments()
    {
        return $this->hasMany(Department::class);
    }
}
