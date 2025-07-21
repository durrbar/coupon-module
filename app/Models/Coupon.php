<?php

namespace Modules\Coupon\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Ecommerce\Models\Shop;
use Modules\Ecommerce\Models\User;
use Modules\Ecommerce\Traits\TranslationTrait;
use Modules\Order\Models\Order;

class Coupon extends Model
{
    use SoftDeletes;
    use TranslationTrait;

    protected $table = 'coupons';

    public $guarded = [];

    // protected $appends = ['is_valid'];
    protected $appends = ['is_valid', 'translated_languages'];

    protected $casts = [
        'image' => 'json',
    ];

    protected static function boot()
    {
        parent::boot();
        // Order by updated_at desc
        static::addGlobalScope('order', function (Builder $builder): void {
            $builder->orderBy('updated_at', 'desc');
        });
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'coupon_id');
    }

    /**
     * @return bool
     */
    public function getIsValidAttribute()
    {
        return Carbon::now()->between($this->active_from, $this->expire_at);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }
}
