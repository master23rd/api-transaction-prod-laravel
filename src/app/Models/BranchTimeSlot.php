<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BranchTimeSlot extends Model
{
    protected $guarded = [];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
