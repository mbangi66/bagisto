<?php

namespace Webkul\Attribute\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Attribute\Contracts\AttributeOption as AttributeOptionContract;
use Webkul\Attribute\Database\Factories\AttributeOptionFactory;
use Webkul\Core\Eloquent\TranslatableModel;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Illuminate\Support\Facades\Log;

class AttributeOption extends TranslatableModel implements AttributeOptionContract
{
    use HasFactory;

    public $timestamps = false;

    public $translatedAttributes = ['label'];

    protected $fillable = [
        'admin_name',
        'swatch_value',
        'sort_order',
        'attribute_id',
    ];

    /**
     * Append to the model attributes
     *
     * @var array
     */
    protected $appends = [
        'swatch_value_url',
    ];

    /**
     * Get the attribute that owns the attribute option.
     */
    public function attribute(): BelongsTo
    {
        return $this->belongsTo(AttributeProxy::modelClass());
    }

    /**
     * Get image url for the swatch value.
     */
    public function swatch_value_url()
    {
        if ($this->swatch_value && $this->attribute->swatch_type == 'image') {
            if ($this->isDriverLocal()) {
                return Storage::url($this->swatch_value);
            }
            $cacheUrl = url('cache/small/' . $this->swatch_value);
            return $cacheUrl;
        }
        return null;
    }

    /**
     * Get image url for the swatch value attribute.
     */
    public function getSwatchValueUrlAttribute()
    {
        return $this->swatch_value_url();
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): Factory
    {
        return AttributeOptionFactory::new();
    }

    /**
     * Determine if the storage driver is local.
     */
    private function isDriverLocal(): bool
    {
        $isLocal = Storage::getAdapter() instanceof LocalFilesystemAdapter;
        return $isLocal;
    }
}
