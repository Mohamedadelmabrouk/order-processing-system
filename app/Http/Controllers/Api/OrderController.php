<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\OrderHasPaymentsException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Order\IndexOrderRequest;
use App\Http\Requests\Order\StoreOrderRequest;
use App\Http\Requests\Order\UpdateOrderRequest;
use App\Http\Traits\ResponseTrait;
use App\Services\OrderService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    use ResponseTrait;

    public function __construct(
        private readonly OrderService $orderService
    ) {
    }

    public function index(IndexOrderRequest $request): JsonResponse
    {
        $orders = $this->orderService->listForUser(
            (int) Auth::guard('api')->id(),
            $request->validated('status'),
            (int) ($request->validated('per_page') ?? 15)
        );

        return $this->sendResponse($orders, 'Orders retrieved successfully.');
    }

    public function store(StoreOrderRequest $request): JsonResponse
    {
        $order = $this->orderService->createForUser(
            (int) Auth::guard('api')->id(),
            $request->validated()
        );

        return $this->sendResponse($order, 'Order created successfully.', 201);
    }

    public function show(int|string $order): JsonResponse
    {
        try {
            $result = $this->orderService->findForUser((int) Auth::guard('api')->id(), $order);
        } catch (ModelNotFoundException $e) {
            return $this->sendError('Order not found.', [], 404);
        }

        return $this->sendResponse($result, 'Order retrieved successfully.');
    }

    public function update(UpdateOrderRequest $request, int|string $order): JsonResponse
    {
        try {
            $result = $this->orderService->updateForUser(
                (int) Auth::guard('api')->id(),
                $order,
                $request->validated()
            );
        } catch (ModelNotFoundException $e) {
            return $this->sendError('Order not found.', [], 404);
        }

        return $this->sendResponse($result, 'Order updated successfully.');
    }

    public function destroy(int|string $order): JsonResponse
    {
        try {
            $this->orderService->deleteForUser((int) Auth::guard('api')->id(), $order);
        } catch (ModelNotFoundException $e) {
            return $this->sendError('Order not found.', [], 404);
        } catch (OrderHasPaymentsException $e) {
            return $this->sendError($e->getMessage(), [], 409);
        }

        return $this->sendResponse(null, 'Order deleted successfully.');
    }
}
