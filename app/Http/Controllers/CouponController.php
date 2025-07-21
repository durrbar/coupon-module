<?php

namespace Modules\Coupon\Http\Controllers;

use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Modules\Core\Exceptions\DurrbarException;
use Modules\Core\Http\Controllers\CoreController;
use Modules\Coupon\Http\Requests\CouponRequest;
use Modules\Coupon\Http\Requests\UpdateCouponRequest;
use Modules\Coupon\Http\Resources\CouponResource;
use Modules\Coupon\Repositories\CouponRepository;
use Modules\Role\Enums\Permission;
use Prettus\Validator\Exceptions\ValidatorException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class CouponController extends CoreController
{
    public $repository;

    public function __construct(CouponRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $limit = $request->limit ?? 15;
        $language = $request->language ?? DEFAULT_LANGUAGE;
        $coupons = $this->fetchCoupons($request, $language)->paginate($limit)->withQueryString();

        return formatAPIResourcePaginate(CouponResource::collection($coupons)->response()->getData(true));
    }

    public function fetchCoupons(Request $request)
    {
        try {
            $language = $request->language ?? DEFAULT_LANGUAGE;
            $user = $request->user();
            $query = $this->repository->whereNotNull('id')->with('shop');
            if ($user) {
                switch (true) {
                    case $user->hasPermissionTo(Permission::SUPER_ADMIN):
                        $query->where('language', $language);
                        break;

                    case $user->hasPermissionTo(Permission::STORE_OWNER):
                        $this->repository->hasPermission($user, $request->shop_id)
                            ? $query->where('shop_id', $request->shop_id)
                            : $query->where('user_id', $user->id)->whereIn('shop_id', $user->shops->pluck('id'));
                        $query->where('language', $language);
                        break;

                    case $user->hasPermissionTo(Permission::STAFF):
                        $query->where('shop_id', $request->shop_id)->where('language', $language);
                        break;

                    default:
                        $query->where('language', $language);
                        break;
                }
            } else {
                if ($request->shop_id) {
                    $query->where('shop_id', $request->shop_id);
                }
                $query->where('language', $language);
            }

            return $query;
        } catch (DurrbarException $e) {
            throw new DurrbarException(SOMETHING_WENT_WRONG, $e->getMessage());
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return LengthAwarePaginator|Collection|mixed
     *
     * @throws ValidatorException
     */
    public function store(CouponRequest $request)
    {
        try {
            return $this->repository->storeCoupon($request);
        } catch (DurrbarException $e) {
            throw new DurrbarException(COULD_NOT_CREATE_THE_RESOURCE, $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function show(Request $request, $params)
    {
        try {
            $language = $request->language ?? DEFAULT_LANGUAGE;
            try {
                if (is_numeric($params)) {
                    $params = (int) $params;

                    return $this->repository->where('id', $params)->firstOrFail();
                }

                return $this->repository->where('code', $params)->where('language', $language)->firstOrFail();
            } catch (Throwable $e) {
                throw new ModelNotFoundException(NOT_FOUND);
            }
        } catch (DurrbarException $e) {
            throw new DurrbarException(NOT_FOUND);
        }
    }

    /**
     * Verify Coupon by code.
     *
     * @param  int  $id
     * @return mixed
     */
    public function verify(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'sub_total' => 'required|numeric',
        ]);
        try {
            return $this->repository->verifyCoupon($request);
        } catch (DurrbarException $e) {
            throw new DurrbarException(NOT_FOUND);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  CouponRequest  $request
     * @param  int  $id
     * @return JsonResponse
     */
    public function update(UpdateCouponRequest $request, $id)
    {
        try {
            $request->id = $id;

            return $this->updateCoupon($request);
        } catch (DurrbarException $th) {
            throw new DurrbarException();
        }
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    public function updateCoupon(Request $request)
    {
        $id = $request->id;
        $dataArray = $this->repository->getDataArray();

        try {
            $code = $this->repository->findOrFail($id);

            if ($request->has('language') && $request['language'] === DEFAULT_LANGUAGE) {
                $updatedCoupon = $request->only($dataArray);
                if (! $request->user()->hasPermissionTo(Permission::SUPER_ADMIN)) {
                    $updatedCoupon['is_approve'] = false;
                }
                $nonTranslatableKeys = ['language', 'image', 'description', 'id'];
                foreach ($nonTranslatableKeys as $key) {
                    if (isset($updatedCoupon[$key])) {
                        unset($updatedCoupon[$key]);
                    }
                }
                $this->repository->where('code', $code->code)->update($updatedCoupon);
            }

            return $this->repository->update($request->only($dataArray), $id);
        } catch (Exception $e) {
            throw new HttpException(404, NOT_FOUND);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function destroy($id)
    {
        try {
            return $this->repository->findOrFail($id)->delete();
        } catch (DurrbarException $e) {
            throw new DurrbarException(NOT_FOUND);
        }
    }

    public function approveCoupon(Request $request)
    {

        try {
            if (! $request->user()->hasPermissionTo(Permission::SUPER_ADMIN)) {
                throw new DurrbarException(NOT_AUTHORIZED);
            }
            $coupon = $this->repository->findOrFail($request->id);
            $coupon->update(['is_approve' => true]);

            return $coupon;
        } catch (DurrbarException $th) {
            throw new DurrbarException(SOMETHING_WENT_WRONG);
        }
    }

    public function disApproveCoupon(Request $request)
    {
        try {
            if (! $request->user()->hasPermissionTo(Permission::SUPER_ADMIN)) {
                throw new DurrbarException(NOT_AUTHORIZED);
            }
            $coupon = $this->repository->findOrFail($request->id);
            $coupon->is_approve = false;
            $coupon->save();

            return $coupon;
        } catch (DurrbarException $th) {
            throw new DurrbarException(SOMETHING_WENT_WRONG);
        }
    }
}
