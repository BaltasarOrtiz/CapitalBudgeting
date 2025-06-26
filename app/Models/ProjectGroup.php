<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'optimization_id',
        'group_id',
        'project_name',
    ];

    protected $casts = [
        'group_id' => 'integer',
    ];

    public function optimization(): BelongsTo
    {
        return $this->belongsTo(Optimization::class);
    }

    public function scopeByGroup($query, int $groupId)
    {
        return $query->where('group_id', $groupId);
    }
}
