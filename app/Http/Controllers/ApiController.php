<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * @OA\Info(
 *     title="Banque API",
 *     version="1.0.0",
 *     description="API REST documentation for Banque project"
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
     *     path="/api/v1/user",
     *     summary="Get user information",
     *     @OA\Response(response=200, description="User data")
     * )
     */
    public function getUser(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => ['message' => 'User endpoint'],
            'timestamp' => now()->toISOString(),
            'path' => $request->path(),
            'traceId' => uniqid()
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/test",
     *     summary="Test endpoint",
     *     @OA\Response(response=200, description="Test response")
     * )
     */
    public function test(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => ['message' => 'Test endpoint working'],
            'timestamp' => now()->toISOString(),
            'path' => $request->path(),
            'traceId' => uniqid()
        ]);
    }
}