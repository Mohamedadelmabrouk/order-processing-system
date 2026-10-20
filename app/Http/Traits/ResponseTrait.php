<?php

namespace App\Http\Traits;

use Illuminate\Http\JsonResponse;

trait ResponseTrait
{
    public function sendResponse($data = null, string $message = 'Success', int $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    public function sendError(string $message, $errors = [], int $code = 400): JsonResponse
    {
        $res = [
            'success' => false,
            'message' => $message,
        ];

        if (! empty($errors)) {
            $res['errors'] = $errors;
        }

        return response()->json($res, $code);
    }
}