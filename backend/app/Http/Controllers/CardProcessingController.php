<?php

namespace App\Http\Controllers;

use App\Contracts\ICardProcessor;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CardProcessingController extends Controller
{
    protected $processor;

    public function __construct(ICardProcessor $processor)
    {
        $this->processor = $processor;
    }

    public function verifyMerchant(Request $request)
    {
        $request->merge($request->query());
        $request->validate([
            'merchant_username' => 'required|string',
            'merchant_password' => 'required|string',
            'merchant_id' => 'required|string'
        ]);

        try
        {
            $response = $this->processor->verifyMerchant(
                $request->merchant_username, 
                $request->merchant_password, 
                $request->merchant_id
            );
        }
        catch (Exception $e)
        {
            return response()->error('Something went wrong while trying to verify merchant.');
        }

        if (!$response['success'])
        {
            return response()->error($response['message']);
        }

        return response()->success();
    }

    public function getTerminals(Request $request)
    {
        $request->validate(['merchant_id' => 'required|string|exists:preferences,merchant_id']);

        try
        {
            $response = $this->processor->getTerminals($request->merchant_id);
        }
        catch (Exception $e)
        {
            return response()->error('There was an error trying to communicate with our card processors servers.');
        }

        if (!$response['success'])
        {
            return response()->error($response['message']);
        }

        return response()->success(['terminals' => $response['terminals']]);
    }

    public function disconnectTerminal(Request $request)
    {
        $request->validate([
            'hsn' => 'required|string',
            'session_key' => 'required|string'
        ]);
        $body = $request->all();
        $merchantId = $request->user()->preferences->merchant_id;

        $disconnected = $this->processor->disconnectTerminal($merchantId, $body['hsn'], $body['session_key']);

        if (!$disconnected)
        {
            return response()->error('Was unable to disconnect terminal.');
        }

        return response()->success();
    }

    public function connectToTerminal(Request $request)
    {
        $request->validate(['hsn' => 'required|string']);
        $merchantId = $request->user()->preferences->merchant_id;

        $response = $this->processor->connectToTerminal($merchantId, $request->input('hsn'));

        if (!$response['success'])
        {
            return response()->error($response['message']);
        }

        return response()->success(['session_key' => $response['session_key']]);
    }

    public function authorizeTerminalTransaction(Request $request)
    {
        $request->validate([
            'hsn' => 'required|string',
            'session_key' => 'required|string',
            'amount' => 'required|int',
            'is_debit' => 'required|boolean'
        ]);
        $body = $request->all();
        $merchantId = $request->user()->preferences->merchant_id;
        
        try
        {
            $response = $this->processor->authorizeTerminalTransaction($merchantId, $body['hsn'], $body['session_key'], (string) $body['amount'], $body['is_debit']);
        }
        catch (Exception $e)
        {
            if ($e->getCode() == 100) {
                return response()->error($e->getMessage());
            }

            return response()->error('Something went wrong while trying to process payment try again.');
        }
        
        return response()->success([
            'processor_reference' => $response['reference_id'], 
            'processing_details' => isset($response['processing_details'])
                ? $response['processing_details']
                : null
        ]);
    }

    public function refundTransaction(Request $request)
    {
        $request->validate([
            'retref' => 'required|string',
            'amount' => 'required|int'
        ]);
        $body = $request->all();
        $merchantId = $request->user()->preferences->merchant_id;

        try
        {
            $response = $this->processor->refundTransaction($merchantId, $body['retref'], $body['amount']);
        }
        catch (Exception $e)
        {
            if ($e->getCode() == 100)
            {
                return response()->error($e->getMessage());
            }

            return response()->error('Something went wrong while trying to process refund.');
        }

        return response()->success(['retref' => $response['retref']]);
    }
}
