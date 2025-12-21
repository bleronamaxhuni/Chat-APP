<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *    title="Chat APP API",
 *    version="1.0.0",
 *    description="API documentation for Chat Application - A social media chat application with posts, comments, likes, friendships, and real-time messaging",
 *    @OA\Contact(
 *        email="support@chatapp.com"
 *    )
 * )
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="API Server"
 * )
 * @OA\SecurityScheme(
 *     type="http",
 *     securityScheme="bearerAuth",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Authentication using Laravel session-based authentication"
 * )
 */
abstract class Controller
{
    //
}
