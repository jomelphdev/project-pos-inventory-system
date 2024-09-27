<?php

namespace App\Services;

use App\Contracts\ICardProcessor;
use App\Models\Preference;
use DateTime;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Money\Money;

class CardConnect implements ICardProcessor
{
    public function verifyMerchant(string $username, string $password, string $merchantId)
    {
        $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => $this->generateBase64Auth($merchantId, $username, $password)
            ])
            ->get(config('services.cardconnect.gateway_url') . 'inquireMerchant/' . $merchantId);

        $body = $response->json();
        switch ($response->status())
        {
            case 200:
                if (isset($body['message'])) {
                    Log::channel('single')->error(new Exception($body['message']));
                    return [
                        'success' => false,
                        'message' => $body['message']
                    ];
                }
                
                return [
                    'success' => true
                ];
            case 401:
                return [
                    'success' => false,
                    'message' => 'Invalid merchant ID double check and try again.'
                ];
        }
    }
    
    public function getTerminals(string $merchantId)
    {
        $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => config('services.cardconnect.bolt_api_key')
            ])
            ->post(
                config('services.cardconnect.bolt_api_url') . 'v2/listTerminals',
                ['merchantId' => $merchantId]
            );

        $body = $response->json();
        switch ($response->status())
        {
            case 200:
                return [
                    'success' => true,
                    'terminals' => $body['terminals']
                ];
            case 500:
                return [
                    'success' => false,
                    'message' => $response['errorMessage']
                ];
        }
    }

    public function connectToTerminal(string $merchantId, string $hsn, bool $force=false)
    {
        $existingSessionKey = Cache::get($hsn . ':sessionKey');
        if ($existingSessionKey != null) return array_merge(
            ['success' => true], 
            $existingSessionKey
        );

        $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => config('services.cardconnect.bolt_api_key')
            ])
            ->post(
                config('services.cardconnect.bolt_api_url') . 'v2/connect',
                [
                    'merchantId' => $merchantId,
                    'hsn' => $hsn,
                    'force' => $force
                ]
            );

        if ($response->status() == 500)
        {
            $errorCode = $response->json()['errorCode'];
            switch ($errorCode)
            {
                case 6:
                    throw new Exception('Terminal is not connected to Bolt service.', 100);
                case 7:
                    return $this->connectToTerminal($merchantId, $hsn, true);
            }
        }
            
        $key = $response->header('X-CardConnect-SessionKey');
        $sessionKey = substr($key, 0, strpos($key, ';'));
        $expireTime = substr($key, strpos($key, '=') + 1);

        $expireDateTime = new DateTime($expireTime);
        $diff = $expireDateTime->diff(new DateTime());
        $daysInSecs = $diff->format('%r%a') * 24 * 60 * 60;
        $hoursInSecs = $diff->h * 60 * 60;
        $minsInSecs = $diff->i * 60;
        $diffSecs = $daysInSecs + $hoursInSecs + $minsInSecs + $diff->s;
        
        Cache::put($hsn . ':sessionKey', [
            'session_key' => $sessionKey,
            'expires_at' => $expireTime
        ], $diffSecs);
        
        return [
            'success' => true,
            'session_key' => $sessionKey,
            'expires_at' => $expireTime
        ];
    }

    public function disconnectTerminal(string $merchantId, string $hsn)
    {
        $terminalSessionKey = $this->connectToTerminal($merchantId, $hsn)['session_key'];

        $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'X-CardConnect-SessionKey' => $terminalSessionKey,
                'Authorization' => config('services.cardconnect.bolt_api_key')
            ])
            ->post(
                config('services.cardconnect.bolt_api_url') . 'v2/disconnect',
                [
                    'merchantId' => $merchantId,
                    'hsn' => $hsn
                ]
            );

        if ($response->status() != 200)
        {
            $body = $response->json();

            if ($body['errorCode'] == 1 && $body['errorMessage'] == 'Session key for hsn ' . $hsn . ' was not valid')
            {
                return true;
            }

            return false;
        }

        return true;
    }

    public function authorizeTerminalTransaction(string $merchantId, string $hsn, string $amount, bool $isDebit = false, bool $isRetry = false)
    {
        $terminalSessionKey = $this->connectToTerminal($merchantId, $hsn)['session_key'];

        $payload = [
            'merchantId' => $merchantId,
            'hsn' => $hsn,
            'amount' => $amount,
            'includeSignature' => 'true',
            'beep' => 'true',
        ];

        if ($isDebit)
        {
            $payload['aid'] = 'debit';
            $payload['includePIN'] = 'true';
            $payload['includeSignature'] = 'false';
        }
        
        $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'X-CardConnect-SessionKey' => $terminalSessionKey,
                'Authorization' => config('services.cardconnect.bolt_api_key')
            ])
            ->post(
                config('services.cardconnect.bolt_api_url') . 'v3/authCard',
                $payload
            );
        $body = $response->json();
            
        if ($response->status() != 200)
        {
            switch ($body['errorCode'])
            {
                case 1:
                    $validMsg = [
                        'Session key for hsn ' . $hsn . ' was not valid',
                        'SessionKey header is required'
                    ];
                    if (in_array($body['errorMessage'], $validMsg) && !$isRetry)
                    {
                        return $this->authorizeTerminalTransaction($merchantId, $hsn, $amount, $isDebit, true);
                    }
                    break;
                case 8:
                    throw new Exception('Customer canceled the transaction.', 100);
            }

            throw new Exception($body['errorCode'] . ': ' . $body['errorMessage']);
        } else if ($body['respstat'] == 'B')
        {
            throw new Exception('Please retry transaction.', 100);
        } else if ($body['respstat'] == 'C')
        {
            throw new Exception('Card was declined.', 100);
        }

        $referenceId = $body['retref'];

        $response = [
            'success' => true,
            'reference_id' => $referenceId
        ];

        if (isset($body['emvTagData']))
        {
            $emvTagData = json_decode($body['emvTagData'], true);
            $response['processing_details'] = [
                'auth_code' => $body['authcode'],
                'mode' => $emvTagData['Mode'],
                'entry_method' => $emvTagData['Entry method'],
                'card_label' => $emvTagData['Application Label']
            ];
        }

        return $response;
    }

    public function authorizeOnlineTransaction(string $merchantId, string $amount, string $account, string $expiry)
    {
        $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => $this->generateBase64Auth($merchantId)
            ])
            ->post(
                config('services.cardconnect.gateway_url') . 'auth',
                [
                    'merchid' => $merchantId,
                    'amount' => $amount,
                    'account' => $account,
                    'expiry' => $expiry,
                    'capture' => 'Y'
                ]
            );

        return $response->json();
    }

    public function getTransaction(string $merchantId, string $referenceId)
    {
        $response = Http::withHeaders([
                'Authorization' => $this->generateBase64Auth($merchantId)
            ])
            ->get(
                config('services.cardconnect.gateway_url') . 'inquire/' . $referenceId . '/' . $merchantId
            );

        $body = $response->json();

        if (isset($body['emvTagData']))
        {
            $body['emvTagData'] = json_decode($body['emvTagData'], true);
        }
        
        return $body;
    }

    public function captureTransaction(string $merchantId, string $referenceId, int $amount=null)
    {
        $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => $this->generateBase64Auth($merchantId)
            ])
            ->post(
                config('services.cardconnect.gateway_url') . 'capture',
                [
                    'merchid' => $merchantId,
                    'retref' => $referenceId,
                    'amount' => $amount
                ]
            );

        return $response->json();
    }

    public function refundTransaction(string $merchantId, string $referenceId, int $amount=null)
    {
        $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => $this->generateBase64Auth($merchantId)
            ])
            ->post(
                config('services.cardconnect.gateway_url') . 'refund',
                [
                    'merchid' => $merchantId,
                    'retref' => $referenceId,
                    'amount' => $amount
                ]
            );

        $body = $response->json();
        
        if ($body['respstat'] != 'A')
        {
            if ($body['respstat'] == 'C' && $body['respcode'] == '28')
            {
                return $this->voidTransaction($merchantId, $referenceId, $amount);
            }
            
            throw new Exception('Error Code: ' . $body['respcode'] . ' Status: ' . $body['respstat'] . ' Message: ' . $body['resptext']);
        }

        return $body;
    }

    public function voidTransaction(string $merchantId, string $referenceId, int $amount=null)
    {
        $transaction = $this->getTransaction($merchantId, $referenceId);
        $transactionAmount = (int) ($transaction['amount'] * 100);

        if ($transaction['setlstat'] == 'Queued for Capture' && $transactionAmount != $amount)
        {
            $amount = Money::USD($transactionAmount)->subtract(Money::USD($amount));
            return $this->captureTransaction($merchantId, $referenceId, (int) $amount->getAmount());
        }

        $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => $this->generateBase64Auth($merchantId)
            ])
            ->post(
                config('services.cardconnect.gateway_url') . 'void',
                [
                    'merchid' => $merchantId,
                    'retref' => $referenceId,
                    'amount' => $amount
                ]
            );

        return $response->json();
    }

    public function generateBase64Auth(string $merchantId, string $username=null, string $password=null)
    {
        if (!$username || !$password)
        {
            $preferences = Preference::select('merchant_id', 'merchant_username', 'merchant_password')->where('merchant_id', $merchantId)->first();
            $username = $preferences->merchant_username;
            $password = $preferences->merchant_password;
        }

        return 'Basic ' . base64_encode($username . ':' . $password);
    }
}

?>