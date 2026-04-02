<?php

declare(strict_types=1);

namespace Modules\Coupon\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Modules\Coupon\Enums\CouponType;

class CouponRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $language = $this->language ?? DEFAULT_LANGUAGE;
        if ($this->has('type') && $this->type === CouponType::PercentageCoupon->value) {
            $rules['amount'] = ['required', 'numeric', 'min:0', 'max:100'];
        } else {
            $rules['amount'] = ['required', 'numeric', 'min:0'];
        }

        return [
            'code' => ['required', Rule::unique('coupons')->where('language', $language)],
            'amount' => $rules['amount'],
            'minimum_cart_amount' => ['required', 'numeric', 'min:0'],
            'shop_id' => ['nullable', 'exists:Modules\Ecommerce\Models\Shop,id'],
            'type' => ['required', new Enum(CouponType::class)],
            'description' => ['nullable', 'string'],
            'image' => ['array'],
            'language' => ['nullable', 'string'],
            'active_from' => ['required', 'date'],
            'expire_at' => ['required', 'date'],
        ];
    }

    /**
     * Get the error messages that apply to the request parameters.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'code.required' => 'Code field is required and it should be unique',
            'amount.required' => 'Amount field is required',
            'minimum_cart_amount.required' => 'Cart Minimum Amount field is required',
            'type.required' => 'Coupon type is required and it can be only '.CouponType::FixedCoupon->value.' or '.CouponType::PercentageCoupon->value.' or '.CouponType::FreeShippingCoupon->value.'',
            'type.in' => 'Type only can be '.CouponType::FixedCoupon->value.' or '.CouponType::PercentageCoupon->value.' or '.CouponType::FreeShippingCoupon->value.'',
            'active_from.required' => 'Active from field is required',
            'expire_at.required' => 'Expire at field is required',
        ];
    }

    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json($validator->errors(), 422));
    }
}
