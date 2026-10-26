<?php

namespace App\Exceptions;

use Throwable;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
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
     */
    public function register(): void
    {
        // Seller logout with an expired/stale CSRF token (page left open past the
        // session lifetime, or restored with the Back button): finish the logout
        // and go to the login page instead of showing "419 Page Expired".
        $this->renderable(function (Throwable $e, $request) {
            $expired = $e instanceof TokenMismatchException
                || ($e instanceof HttpExceptionInterface && $e->getStatusCode() === 419);

            if ($expired && $request->is('seller/seller-logout')) {
                Auth::guard('seller')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('seller.login')->with('success', 'You have been logged out.');
            }
        });

        $this->renderable(function (MethodNotAllowedHttpException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json(['status' => false, 'message' => $e->getMessage()], 405);
            }
        });

        $this->renderable(function (NotFoundHttpException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json(['status' => false, 'message' => 'Api not found..!!'], 404);
            } else if ($request->is('storage/*') && pathinfo($request->getPathInfo(), PATHINFO_EXTENSION)) {
                return response()->file(public_path('assets/img/img-not-found.png'));
            }
        });
    }
}
