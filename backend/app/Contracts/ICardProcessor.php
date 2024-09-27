<?php

namespace App\Contracts;

interface ICardProcessor
{
    /** @return mixed */
    public function verifyMerchant(string $username, string $password, string $merchantId);
    /** @return mixed */
    public function getTerminals(string $merchantId);
    /** 
     * @param string $merchantId
     * @param string $hsn Hardware serial number of terminal
     * @return string Terminal session key
     */
    public function connectToTerminal(string $merchantId, string $hsn);
    /** 
     * @param string $merchantId
     * @param string $hsn Hardware serial number of terminal
     * @return boolean True if disconnect false if not
     */
    public function disconnectTerminal(string $merchantId, string $hsn);
    /** 
     * @param string $merchantId
     * @param string $hsn Hardware serial number of terminal
     * @param string $amount Amount to transact
     * @param bool $isDebit Is it a debit card transaction or not
     * @return mixed 
     */
    public function authorizeTerminalTransaction(
        string $merchantId,
        string $hsn,
        string $amount,
        bool $isDebit=false
    );
    /** 
     * @param string $merchantId
     * @param string $amount Amount to transact
     * @param string $account Tokenized card number
     * @param string $expiry Expiration of card in MMYY format ex: 1218
     * @return mixed
     */
    public function authorizeOnlineTransaction(
        string $merchantId,
        string $amount,
        string $account,
        string $expiry
    );

    /** @return mixed */
    public function getTransaction(string $merchantId, string $referenceId);

    /** @return mixed */
    public function captureTransaction(string $merchantId, string $referenceId, int $amount=null);

    /** @return mixed */
    public function refundTransaction(string $merchantId, string $referenceId, int $amount=null);

    /** @return mixed */
    public function voidTransaction(string $merchantId, string $referenceId, int $amount=null);
}

?>