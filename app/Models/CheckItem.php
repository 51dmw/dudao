<?php

namespace App\Models;

use App\Enums\IssueLevel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CheckItem extends Model
{
    public $timestamps = false;

    protected $fillable = ['dimension_id', 'name', 'points', 'default_level', 'sort', 'is_active'];

    protected function casts(): array
    {
        return [
            'default_level' => IssueLevel::class,
            'is_active'     => 'boolean',
        ];
    }

    public function dimension(): BelongsTo
    {
        return $this->belongsTo(Dimension::class);
    }
}
