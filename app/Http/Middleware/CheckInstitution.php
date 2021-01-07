<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckInstitution
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $institutions = $request->user()->institutions()->get();

        if (sizeof($institutions) === 0) {
            return response()->json([
                'data' => null,
                'msg' => [
                    'summary' => 'No tiene una institución asignada (check-institution)',
                    'detail' => 'Comunicate con el administrador',
                    'code' => '403'
                ]
            ], 403);
        }

        return $next($request);
    }
}
