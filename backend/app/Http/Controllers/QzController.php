<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class QzController extends Controller
{
    public function signData(Request $request)
    {
        $dataToSign = $request->input('data');

        if ($dataToSign)
        {
            $key = openssl_get_privatekey(config("services.qztray.key"));
            $signature = null;
            openssl_sign($dataToSign, $signature, $key, "sha512");

            return response(base64_encode($signature), 200, ['Content-Type' => 'text/plain']);
        }
    }

    public function getCert()
    {
        return response(config("services.qztray.cert"), 200, ['Content-Type' => 'text/plain']);
    }
}
