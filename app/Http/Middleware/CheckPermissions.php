<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckPermissions
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $roles = $request->user()->roles()->where('institution_id',$request->institution_id)->get();

        if (sizeof($roles) === 0) {
            return response()->json([
                'data' => $roles,
                'msg' => [
                    'summary' => 'No tiene un rol asignado',
                    'detail' => 'Comunicate con el administrador',
                    'code' => '403'
                ]
            ], 403);
        }
        return $next($request);
    }
}
