<?php

namespace Modules\Coupon\Enums;

use BenSampo\Enum\Enum;

/**
 * Class RoleType
 */
final class CouponTargetType extends Enum
{
    public const GLOBAL_CUSTOMER = 'global_customer';

    public const VERIFIED_CUSTOMER = 'verified_customer';
}
