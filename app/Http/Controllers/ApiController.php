<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * @OA\Info(
 *     title="Abdoulaye Diome API",
 *     version="1.0.0",
 *     description="API documentation for Abdoulaye Diome's Laravel project"
 * )
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="API server"
 * )
 */
class ApiController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/user",
     *     summary="Get user information",
     *     @OA\Response(response=200, description="User data")
     * )
     */
    public function getUser(Request $request)
    {
        return response()->json(['message' => 'User endpoint']);
    }

    /**
     * @OA\Get(
     *     path="/api/test",
     *     summary="Test endpoint",
     *     @OA\Response(response=200, description="Test response")
     * )
     */
    public function test(Request $request)
    {
        return response()->json(['message' => 'Test endpoint working']);
    }
}