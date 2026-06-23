<?php

namespace App\Models;

use App\Enums\Grade;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Inspection extends Model
{
    protected $fillable = [
        'website_id', 'inspector_id', 'inspect_date',
        'score_product', 'score_content', 'score_ux', 'score_ad', 'score_exec',
        'score_adjust', 'total_score', 'grade', 'status', 'remark',
    ];

    protected function casts(): array
    {
        return [
            'inspect_date' => 'date',
            'total_score'  => 'decimal:1',
            'grade'        => Grade::class,
        ];
    }

    public function website(): BelongsTo   { return $this->belongsTo(Website::class); }
    public function inspector(): BelongsTo { return $this->belongsTo(User::class, 'inspector_id'); }
    public function results(): HasMany     { return $this->hasMany(InspectionResult::class); }
    public function issues(): HasMany      { return $this->hasMany(Issue::class); }
}
