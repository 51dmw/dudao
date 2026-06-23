<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Dimension extends Model
{
    public $timestamps = false;

    protected $fillable = ['name', 'code', 'max_score', 'sort'];

    public function checkItems(): HasMany
    {
        return $this->hasMany(CheckItem::class);
    }
}
