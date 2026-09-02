<?php

namespace App\Models;

use App\CategoryType;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $workspace_id
 * @property string $name
 * @property CategoryType $type
 * @property string|null $icon
 * @property string $color
 * @property bool $is_archived
 */
class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    protected $fillable = ['workspace_id', 'name', 'type', 'icon', 'color', 'is_archived'];

    protected function casts(): array
    {
        return [
            'type' => CategoryType::class,
            'is_archived' => 'boolean',
        ];
    }

    /** @return BelongsTo<Workspace, $this> */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /** @return HasMany<Transaction, $this> */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
