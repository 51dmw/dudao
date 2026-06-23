<?php

namespace App\Models;

use App\Enums\Grade;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Website extends Model
{
    protected $fillable = [
        'name', 'domain', 'pm_id', 'operator_id', 'seo_id', 'manager_id',
        'online_at', 'status', 'current_score', 'current_grade', 'last_inspected_at',
    ];

    protected function casts(): array
    {
        return [
            'online_at'         => 'date',
            'last_inspected_at' => 'datetime',
            'current_score'     => 'decimal:1',
            'current_grade'     => Grade::class,
        ];
    }

    public function pm(): BelongsTo       { return $this->belongsTo(User::class, 'pm_id'); }
    public function operator(): BelongsTo { return $this->belongsTo(User::class, 'operator_id'); }
    public function seo(): BelongsTo      { return $this->belongsTo(User::class, 'seo_id'); }
    public function manager(): BelongsTo  { return $this->belongsTo(User::class, 'manager_id'); }

    public function inspections(): HasMany { return $this->hasMany(Inspection::class); }
    public function issues(): HasMany      { return $this->hasMany(Issue::class); }
    public function suggestions(): HasMany { return $this->hasMany(Suggestion::class); }

    // 未关闭问题数
    public function openIssuesCount(): int
    {
        return $this->issues()
            ->whereNotIn('status', ['closed', 'evaluating'])
            ->count();
    }
}
