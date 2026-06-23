<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IssueLog extends Model
{
    public $timestamps = false;

    protected $fillable = ['issue_id', 'from_status', 'to_status', 'operator_id', 'note', 'created_at'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function issue(): BelongsTo    { return $this->belongsTo(Issue::class); }
    public function operator(): BelongsTo { return $this->belongsTo(User::class, 'operator_id'); }
}
