<?php

namespace App\Models;

use App\Support\MenuImage;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Menu extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'code',
        'name',
        'description',
        'image_path',
        'price',
        'is_signature',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_signature' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    protected function imageUrl(): Attribute
    {
        return Attribute::make(
            get: function (): ?string {
                if (blank($this->image_path)) {
                    return null;
                }

                $disk = Storage::disk('menus');

                if (! $disk->exists($this->image_path)) {
                    return null;
                }

                return $disk->url($this->image_path);
            },
        );
    }

    /**
     * srcset WebP (320/640/960) bila varian sudah dibuat lewat images:optimize.
     */
    protected function imageSrcset(): Attribute
    {
        return Attribute::make(
            get: function (): ?string {
                if (blank($this->image_path)) {
                    return null;
                }

                return MenuImage::srcset($this->image_path);
            },
        );
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function transactionItems(): HasMany
    {
        return $this->hasMany(TransactionItem::class);
    }
}
