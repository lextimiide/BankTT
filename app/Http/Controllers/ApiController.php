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
 *     url="http://localhost:8000",
 *     description="Development server"
 * )
 * @OA\Server(
 *     url="https://bankt-1.onrender.com",
 *     description="Production server"
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
     *     description="Simple test endpoint that returns JSON response",
     *     @OA\Response(
     *         response=200,
     *         description="Successful test response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="message", type="string", example="Test endpoint working")
     *             ),
     *             @OA\Property(property="timestamp", type="string", format="date-time"),
     *             @OA\Property(property="path", type="string"),
     *             @OA\Property(property="traceId", type="string")
     *         )
     *     )
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