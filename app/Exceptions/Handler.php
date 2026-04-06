<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        $this->renderable(function (ThrottleRequestsException $e, Request $request) {
            // Keep API behavior intact.
            if ($request->expectsJson()) {
                return null;
            }

            // Friendly UX for auth/password throttling.
            $routeName = optional($request->route())->getName();
            if (in_array($routeName, ['password.email', 'password.update'], true)) {
                return back()
                    ->withErrors(['email' => 'Muitas tentativas. Aguarde 1 minuto e tente novamente.'])
                    ->withInput($request->only('email'));
            }

            return null;
        });
    }
}
