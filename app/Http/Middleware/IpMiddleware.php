<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class IpMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next) {
      $ip = $request->ip();
      $datas = \cache('blocked_ips',[]);
      if(count($datas) == 0) {
        $datas = DB::table('blocked_ips')->select(['ip','description'])->get();
        \cache(
          ['blocked_ips' => $datas],
          now()->addDays(365)
        );
      }
      foreach ($datas as $data) {
        if($data->ip == $ip) {
          return [
            'error' => true,
            'open_verify_email' => false,
            'message' => $data->description
          ];
        }
      }
      return $next($request);
    }
}
