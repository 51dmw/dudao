<?php

namespace App\Models;

use App\Enums\Device;
use App\Enums\IssueLevel;
use App\Enums\IssueStatus;
use App\Enums\PageType;
use App\Enums\RecheckResult;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Issue extends Model
{
    protected $fillable = [
        'code', 'website_id', 'inspection_id', 'level', 'type', 'page_type', 'device',
        'title', 'description', 'fix_suggestion', 'page_url', 'reporter_id', 'assignee_id',
        'due_at', 'status', 'repeat_count', 'recheck_result', 'remark', 'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'level'          => IssueLevel::class,
            'status'         => IssueStatus::class,
            'page_type'      => PageType::class,
            'device'         => Device::class,
            'recheck_result' => RecheckResult::class,
            'due_at'         => 'datetime',
            'closed_at'      => 'datetime',
        ];
    }

    // 手动建单未填编号时自动生成 #001 形式（IssueFactory 显式传值则不覆盖）
    protected static function booted(): void
    {
        static::creating(function (Issue $issue) {
            if (empty($issue->code)) {
                $next = (int) (static::max('id') ?? 0) + 1;
                $issue->code = '#' . str_pad((string) $next, 3, '0', STR_PAD_LEFT);
            }
        });
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
