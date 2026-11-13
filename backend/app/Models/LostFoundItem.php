<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class LostFoundItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'reported_by', 'title', 'kind', 'category', 'description', 'location',
        'occurred_at', 'status', 'tone', 'icon',
    ];

    protected function casts(): array
    {
        return ['occurred_at' => 'datetime'];
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function savedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'saved_items');
    }
}
