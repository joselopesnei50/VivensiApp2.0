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
            if (app()->bound('sentry') && $this->shouldReport($e)) {
                app('sentry')->captureException($e);
            }
        });

        // Sessão expirada (419) num formulário: volta para ele com aviso, em vez da
        // página "Page Expired". Senhas nunca voltam preenchidas.
        // (o Laravel transforma TokenMismatchException em HttpException 419 antes daqui)
        $this->renderable(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, Request $request) {
            if ($e->getStatusCode() !== 419 || $request->expectsJson() || $request->isMethod('GET')) {
                return null;
            }
            $back = url()->previous() !== $request->fullUrl() ? url()->previous() : url('/login');
            return redirect()->to($back)
                ->withInput($request->except(['password', 'password_confirmation', 'current_password', '_token', 'signature']))
                ->with('error', 'Sua sessão expirou por segurança. Confira os dados e envie de novo.');
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
