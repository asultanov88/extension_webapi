<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ValidateRegKey
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
        $request->validate([
            'registrationKey' => 'required',
            'token' => 'required',
        ]);


        /**
        * Getting the clientId from the registrationKey.
        * It is the last numbers after the last period in string.
        */
        $arr = explode('.', $request['registrationKey']);
        $clientId = end($arr);
        $request['clientId'] = $clientId;

        if($request['token'] === $this->generateRegToken($request['registrationKey'])){
            return $next($request);
        }else{
            return response()->
            json(['error' => 'Unauthorized'], 401);
        }
    }

    /**
     * The salt is in .env file as REG_KEY_CRYPT_SALT.
     */
    private function generateRegToken($regKey){

        return crypt($regKey, '$5$rounds=5000'.env('REG_KEY_CRYPT_SALT'));

    }
}
