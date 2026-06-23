<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InspectionResult extends Model
{
    public $timestamps = false;

    protected $fillable = ['inspection_id', 'check_item_id', 'is_normal', 'remark'];

    protected function casts(): array
    {
        return ['is_normal' => 'boolean'];
    }

    public function inspection(): BelongsTo { return $this->belongsTo(Inspection::class); }
    public function checkItem(): BelongsTo  { return $this->belongsTo(CheckItem::class); }
}
