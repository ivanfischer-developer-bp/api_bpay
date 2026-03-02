<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class Cors
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        //Url a la que se le dará acceso en las peticiones
        //Métodos a los que se da acceso
        //Headers de la petición
        $response = $next($request);
        
        $response->headers->set("Access-Control-Allow-Origin", "*");
        $response->headers->set("Access-Control-Allow-Methods", "GET, POST, PUT, DELETE, PATCH, OPTIONS");
        $response->headers->set("Access-Control-Allow-Headers", "X-Requested-With, Content-Type, X-Token-Auth, Authorization, Accept");
        
        return $response;

        // $headers = [
        //     'Access-Controll-Allow-Origin' => "*",
        //     'Access-Control-Allow-Methods' => 'POST, GET, OPTIONS, PUT, DELETE',
        //     'Access-Control-Allow-Headers' => 'X-Requested-With, X-Auth-Token, Content-Type, Origin, Cache-Control, Pragma, Authorization, Accept, Accept-Encoding, X-Login-Origin',
        //     'Access-Control-Allow-Credentials' => 'true'
        // ];

        // if ($request->isMethod("OPTIONS")) {
        //     // The client-side application can set only headers allowed in Access-Control-Allow-Headers
        //     return Response::make('OK', 200, $headers);
        // }

        // $response = $next($request);

    }
}
