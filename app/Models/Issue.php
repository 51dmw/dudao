<?php

namespace App\Models;

use App\Enums\IssueLevel;
use App\Enums\IssueStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Issue extends Model
{
    protected $fillable = [
        'code', 'website_id', 'inspection_id', 'level', 'type', 'title',
        'description', 'page_url', 'reporter_id', 'assignee_id', 'due_at',
        'status', 'repeat_count', 'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'level'     => IssueLevel::class,
            'status'    => IssueStatus::class,
            'due_at'    => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function website(): BelongsTo    { return $this->belongsTo(Website::class); }
    public function inspection(): BelongsTo { return $this->belongsTo(Inspection::class); }
    public function reporter(): BelongsTo   { return $this->belongsTo(User::class, 'reporter_id'); }
    public function assignee(): BelongsTo   { return $this->belongsTo(User::class, 'assignee_id'); }
    public function attachments(): HasMany  { return $this->hasMany(IssueAttachment::class); }
    public function logs(): HasMany         { return $this->hasMany(IssueLog::class); }

    // 是否超期未关闭
    public function isOverdue(): bool
    {
        return $this->due_at
            && $this->status !== IssueStatus::Closed
            && $this->due_at->isPast();
    }

    public function scopeOpen($q)
    {
        return $q->whereNotIn('status', ['closed', 'evaluating']);
    }
}
