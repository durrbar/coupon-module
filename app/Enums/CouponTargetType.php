<?php

declare(strict_types=1);

namespace Modules\Coupon\Enums;

enum CouponTargetType: string
{
    case GlobalCustomer = 'global_customer';
    case VerifiedCustomer = 'verified_customer';
}
