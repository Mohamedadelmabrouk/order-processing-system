<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\InactiveGatewayException;
use App\Exceptions\OrderNotConfirmedException;
use App\Exceptions\PaymentProcessingException;
use App\Exceptions\UnsupportedGatewayException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\IndexPaymentRequest;
use App\Http\Requests\Payment\ProcessPaymentRequest;
use App\Http\Traits\ResponseTrait;
use App\Services\PaymentService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    use ResponseTrait;

    public function __construct(
        private readonly PaymentService $paymentService
    ) {
    }

    public function index(IndexPaymentRequest $request): JsonResponse
    {
        try {
            $payments = $this->paymentService->listForUser(
                (int) Auth::guard('api')->id(),
                $request->validated('order_id') !== null ? (int) $request->validated('order_id') : null,
                $request->validated('status'),
                (int) ($request->validated('per_page') ?? 15)
            );
        } catch (ModelNotFoundException $e) {
            return $this->sendError('Order not found.', [], 404);
        }

        return $this->sendResponse($payments, 'Payments retrieved successfully.');
    }

    public function store(ProcessPaymentRequest $request): JsonResponse
    {
        try {
            $payment = $this->paymentService->process(
                (int) Auth::guard('api')->id(),
                (int) $request->validated('order_id'),
                $request->validated('gateway')
            );
        } catch (ModelNotFoundException $e) {
            return $this->sendError('Order not found.', [], 404);
        } catch (OrderNotConfirmedException $e) {
            return $this->sendError($e->getMessage(), [], 422);
        } catch (UnsupportedGatewayException|InactiveGatewayException $e) {
            return $this->sendError($e->getMessage(), [], 422);
        } catch (PaymentProcessingException $e) {
            return $this->sendError($e->getMessage(), [], 502);
        }

        return $this->sendResponse($payment, 'Payment processed successfully.', 201);
    }

    public function show(int|string $payment): JsonResponse
    {
        try {
            $result = $this->paymentService->findForUser((int) Auth::guard('api')->id(), $payment);
        } catch (ModelNotFoundException $e) {
            return $this->sendError('Payment not found.', [], 404);
        }

        return $this->sendResponse($result, 'Payment retrieved successfully.');
    }
}
