<?php

namespace App\Http\Middleware;

use App\Enums\UserType;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class subscriptionCheckMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    protected $except = [
        'admin/profile*', 
    ];
 
    public function handle(Request $request, Closure $next): Response
    {  
        
        if(!request()->is($this->except)){  
            if(Auth::user() && Auth::user()->user_type != UserType::SUPER_ADMIN ): 
                if(subscriptionCheck()):
                    return $next($request);
                endif;  
                return redirect()->route('subscription.index');
            endif;
        }
        return $next($request);  
    }


}
