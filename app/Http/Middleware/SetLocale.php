<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $locale = $user instanceof User ? $user->locale : config('locales.default');
        $supported = config('locales.supported', []);

        if (! array_key_exists($locale, $supported)) {
            $locale = config('locales.default');
        }

        App::setLocale($supported[$locale]['backend']);

        return $next($request);
    }
}
