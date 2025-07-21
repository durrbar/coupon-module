<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Coupon\Enums\CouponType;

return new class () extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('coupons', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('code');
            $table->text('description')->nullable();
            $table->json('image')->nullable();
            $table->enum('type', CouponType::getValues())->default(CouponType::FIXED_COUPON);
            $table->float('amount')->default(0);
            $table->float('minimum_cart_amount')->default(0);
            $table->string('active_from');
            $table->string('expire_at');
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('coupons');
    }
};
