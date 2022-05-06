<?php
spl_autoload_register(function ($class) {
    require_once($class . '.php');
});

use Ouzo\Utilities\Clock;

class Signature
{
    private $api_key;
    private $partner_id;
    private $timestamp;

    /**
     * Signature constructor.
     * @param $partner_id
     * @param $api_key
     */
    function __construct($partner_id, $api_key)
    {
        $this->api_key = $api_key;
        $this->partner_id = $partner_id;
        $this->timestamp = Clock::now()->getTimestamp();
    }

    /**
     * Generates a sec_key for the provided timestamp or the current timestamp by default
     * @param $timestamp
     * @return array
     * @deprecated deprecated since version 3.0.0
     */
    function generate_sec_key($timestamp = null): array
    {
        $timestamp = $this->isTimestamp($timestamp) ? $timestamp : $this->timestamp;
        $plaintext = intval($this->partner_id) . ":" . $timestamp;
        $hash_signature = hash('sha256', $plaintext);
        $sec_key = '';
        openssl_public_encrypt($hash_signature, $sec_key, base64_decode($this->api_key), OPENSSL_PKCS1_PADDING);
        $sec_key = base64_encode($sec_key);
        $sec_key = $sec_key . "|" . $hash_signature;
        return array("sec_key" => $sec_key, "timestamp" => $timestamp);
    }

    /**
     * Confirms the sec-key against a newly generated sec-key based on the same timestamp
     * @param string $sec_key
     * @return bool
     * @deprecated deprecated since version 3.0.0
     */
     function confirm_sec_key($sec_key): bool
    {
        $sec_key_exploded = explode("|", $sec_key);
        $encrypted = base64_decode($sec_key_exploded[0]);
        $hash_signature = $sec_key_exploded[1];
        $decrypted = '';
        openssl_public_decrypt($encrypted, $decrypted, base64_decode($this->api_key), OPENSSL_PKCS1_PADDING);
        return $hash_signature == $decrypted;
    }

    /**
     * Generates a signature for the provided timestamp or the current timestamp by default
     * @param $timestamp
     * @return array
     */
    function generate_signature($timestamp = null): array
    {
        $timestamp = $timestamp != null ? $timestamp : Clock::now()->format(DateTimeInterface::ISO8601);
        $message = $timestamp . $this->partner_id . "sid_request";
        $sec_key = base64_encode(hash_hmac('sha256', $message, $this->api_key, true));
        return array("signature" => $sec_key, "timestamp" => $timestamp);
    }

    /**
     * Confirms the signature against a newly generated signature based on the same timestamp
     * @param $timestamp
     * @param string $signature
     * @return bool
     */
    function confirm_signature($timestamp, string $signature): bool
    {
        return $signature === $this->generate_signature($timestamp)["signature"];
    }

    /**
     * @param $timestamp
     * @return bool
     */
    private function isTimestamp($timestamp): bool
    {
        if (ctype_digit($timestamp) && strtotime(date('Y-m-d H:i:s', $timestamp)) === (int)$timestamp) {
            return true;
        } else {
            return false;
        }
    }
}