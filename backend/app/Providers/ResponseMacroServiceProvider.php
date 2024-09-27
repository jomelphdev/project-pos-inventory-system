<?php

namespace App\Providers;

use Illuminate\Support\Facades\Response;
use Illuminate\Support\ServiceProvider;

class ResponseMacroServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        Response::macro('success', function ($data=null) {
            return Response::json([
              'success'  => true,
              'data' => $data,
            ]);
        });
    
        Response::macro('error', function (string $message, $status = 400) {
            return Response::json([
              'success'  => false,
              'message' => $message,
            ], $status);
        });

        Response::macro('errorWithData', function (string $message, $data, $status = 400) {
            return Response::json([
              'success'  => false,
              'message' => $message,
              'data' => $data
            ], $status);
        });
    }
}
