<?php

namespace App\Exceptions;

use App\DTOs\ServiceResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Throwable;
use Exception;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
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
    }

    /**
     * Convert an authentication exception into a response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Auth\AuthenticationException  $exception
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($request->expectsJson()) {
            $service = new ServiceResponse("Declined", "Unauthenticated.", 401);

            return response()->json($service->toArray(), $service->error_code);
        }

        return redirect()->guest($exception->redirectTo() ?? route('login'));
    }

    public function render($request, Throwable $e)
    {
        if ($request->wantsJson()) {
            return $this->handleException($request, $e);
        }

        return parent::render($request, $e); // TODO: Change the autogenerated stub
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param Exception|Throwable $exception
     * @return \Symfony\Component\HttpFoundation\Response|JsonResponse
     */
    private function handleException(\Illuminate\Http\Request $request, $exception)
    {
        $message = trim($exception->getMessage()) == "" ? "Cannot process request at this time. Try again." : $exception->getMessage();
        $service = new ServiceResponse("Error", $message, 500);

        if ($exception instanceof MethodNotAllowedException || $exception instanceof MethodNotAllowedHttpException) {
            $service->message = trim($exception->getMessage()) == "" ? "The specified method for this request is not allowed." : $exception->getMessage();
            $service->error_code = 405;

            return response()->json($service->toArray(), $service->error_code);
        }

        if ($exception instanceof NotFoundHttpException || $exception instanceof ModelNotFoundException || $exception instanceof RouteNotFoundException) {
            $service->error_code = 404;
            $service->message = trim($exception->getMessage()) == "" ? "The requested resource was not found." : $exception->getMessage();

            return response()->json($service->toArray(), $service->error_code);
        }

        if ($exception instanceof HttpException) {
            $service->message = trim($exception->getMessage()) == "" ? "" : $exception->getMessage();

            return response()->json($service->toArray(), $service->error_code);
        }

        if ($exception instanceof ValidationException) {
            $original = $this->convertValidationExceptionToResponse($exception, $request)->original;

            $service = new ServiceResponse("Declined", $original['message'], 422, [
                'errors' => $original['errors'],
            ]);

            return response()->json($service->toArray(), $service->error_code);
        }

        if ($exception instanceof AuthenticationException) {
            return $this->unauthenticated($request, $exception);
        }

        if (config('app.debug')) {
            if ($request->wantsJson() && app()->environment(['testing', 'local'])) {
                $service->data = array_merge_recursive($service->data, [
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                ]);

                return response()->json($service->toArray(), $service->error_code);
            }
        }

        return response()->json($service->toArray(), $service->error_code);
    }
}
