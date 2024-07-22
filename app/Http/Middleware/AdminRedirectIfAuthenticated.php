<?php

namespace App\Http\Middleware;

use App\Providers\RouteServiceProvider;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminRedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        // Check if the user is authenticated as an admin
        if (Auth::guard('admin')->check()) {
            // Redirect to the admin home page if authenticated
             return redirect()->route('admin.dashboard');
        } 

        // Proceed with the next middleware or the request handling
        return $next($request);
    }
}
