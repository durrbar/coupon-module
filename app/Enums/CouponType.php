<?php

declare(strict_types=1);

namespace Modules\Coupon\Enums;

enum CouponType: string
{
    case FixedCoupon = 'fixed';
    case PercentageCoupon = 'percentage';
    case FreeShippingCoupon = 'free_shipping';
}
