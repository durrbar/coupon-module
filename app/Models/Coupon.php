<?php

declare(strict_types=1);

namespace Modules\Coupon\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Models\Scopes\OrderByUpdatedAtDescScope;
use Modules\Ecommerce\Traits\TranslationTrait;
use Modules\Order\Models\Order;
use Modules\User\Models\User;
use Modules\Vendor\Models\Shop;

#[ScopedBy([OrderByUpdatedAtDescScope::class])]
#[Table('coupons')]
#[Unguarded]
#[Appends(['is_valid', 'translated_languages'])]
class Coupon extends Model
{
    use HasUuids;
    use SoftDeletes;
    use TranslationTrait;

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'coupon_id');
    }

    /**
     * @return bool
     */
    public function getIsValidAttribute()
    {
        $attributes = $this->getAttributes();

        if (! array_key_exists('active_from', $attributes) || ! array_key_exists('expire_at', $attributes)) {
            return false;
        }

        return Carbon::now()->between($attributes['active_from'], $attributes['expire_at']);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    protected function casts(): array
    {
        return [
            'image' => 'json',
        ];
    }
}
