<?php

namespace App\Models;

use App\Enums\IssueLevel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Suggestion extends Model
{
    protected $fillable = [
        'website_id', 'type', 'module', 'problem', 'suggestion',
        'priority', 'benefit', 'status', 'created_by',
    ];

    protected function casts(): array
    {
        return ['priority' => IssueLevel::class];
    }

    public function website(): BelongsTo { return $this->belongsTo(Website::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}
