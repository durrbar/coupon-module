<?php

use Illuminate\Support\Facades\Route;
use Modules\Coupon\Http\Controllers\CouponController;
use Modules\Role\Enums\Permission;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::group([], function (): void {
    Route::resource('coupon', CouponController::class)->names('coupon');
});

Route::apiResource('coupons', CouponController::class, [
    'only' => ['index', 'show'],
]);
Route::post('coupons/verify', [CouponController::class, 'verify']);

/**
 * *****************************************
 * Authorized Route for Store owner Only
 * *****************************************
 */
Route::group(
    ['middleware' => ['permission:'.Permission::STORE_OWNER, 'auth:sanctum', 'email.verified']],
    function (): void {

        Route::apiResource('coupons', CouponController::class, [
            'only' => ['store', 'destroy'],
        ]);
    }
);

/**
 * ******************************************
 * Authorized Route for Staff & Store Owner
 * ******************************************
 */
Route::group(
    ['middleware' => ['permission:'.Permission::STAFF.'|'.Permission::STORE_OWNER, 'auth:sanctum', 'email.verified']],
    function (): void {

        Route::apiResource('coupons', CouponController::class, [
            'only' => ['update'],
        ]);
    }
);

/**
 * *****************************************
 * Authorized Route for Super Admin only
 * *****************************************
 */
Route::group(['middleware' => ['permission:'.Permission::SUPER_ADMIN, 'auth:sanctum']], function (): void {
    // Route::apiResource('coupons', CouponController::class, [
    //     'only' => ['store', 'update', 'destroy'],
    // ]);

    Route::post('approve-coupon', [CouponController::class, 'approveCoupon']);
    Route::post('disapprove-coupon', [CouponController::class, 'disApproveCoupon']);
});
