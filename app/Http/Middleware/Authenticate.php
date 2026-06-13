<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        // API请求返回JSON，不重定向
        if ($request->expectsJson()) {
            return null;
        }
        
        // 对于API路由，返回错误而不是重定向
        if ($request->is('api/*')) {
            return null;
        }
        
        return route('login');
    }
}
