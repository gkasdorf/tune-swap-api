<?php

namespace App\Helpers;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    /**
     * Returns a successful JSON response with the provided data.
     *
     * `$data` may be a string if you only wish to pass a message, or an array if you wish to pass an array.
     *
     * In the former case, the sent array will be ["success" => true, "message" => $message]
     *
     * In the latter case, the sent array will be what you provide.
     * @param array|string $data
     * @return JsonResponse
     */
    public static function success(array|string $data = []): JsonResponse
    {
        if (is_string($data)) {
            return response()->json([
                "success" => true,
                "message" => $data
            ]);
        }

        $data["success"] = true;
        return response()->json($data, 200);
    }

    /**
     * Returns a failed JSON response with the provided data. `$data` may be an array of data, a HTTP response code,
     * or a message.
     *
     * If you pass a response code as the first parameter, the sent array will be ["success" => false]
     *
     * If you pass a string, the sent array will be ["success" => false, "message" => $message]
     *
     * If you pass an array, the sent array will be your data.
     *
     * Second parameter will be ignored if first parameter is a response code.
     * @param array|int|string $data
     * @param int $code
     * @return JsonResponse
     */
    public static function fail(array|int|string $data = [], int $code = 422): JsonResponse
    {
        if (is_int($data)) {
            return response()->json(["success" => false], $data);
        } else if (is_string($data)) {
            return response()->json([
                "success" => false,
                "message" => $data
            ], $code);
        }

        $data["success"] = false;

        return response()->json($data, $code);
    }

    /**
     * Returns an error JSON response with the provided data. Same as `fail`, you can provide code (default 500) in first
     * or second parameter.
     *
     * If you pass a response code as the first parameter, the sent array will be ["success" => false]
     *
     * If you pass a string, the sent array will be ["success" => false, "message" => $message]
     *
     * If you pass an array, the sent array will be your data.
     *
     * Second parameter will be ignored if first parameter is a response code.
     * @param array|string|int $data
     * @param int $code
     * @return JsonResponse
     */
    public static function error(array|string|int $data = [], int $code = 500): JsonResponse
    {
        if (is_int($data)) {
            return response()->json(["success" => false], $data);
        } else if (is_string($data)) {
            return response()->json([
                "success" => false,
                "message" => $data
            ], $code);
        }

        $data["success"] = false;

        return response()->json($data, $code);
    }

    /**
     * Returns a pre-formatted 403.
     * @return JsonResponse
     */
    public static function forbidden(): JsonResponse
    {
        return self::fail("Forbidden", 403);
    }
}
