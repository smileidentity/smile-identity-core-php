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
     * Generates a signature for the provided timestamp or the current timestamp by default
     * @param $timestamp
     * @return array
     */
    function generate_signature($timestamp = null): array
    {
        $timestamp = $timestamp != null ? $timestamp : Clock::now()->format(DateTimeInterface::ATOM);
        $message = $timestamp . $this->partner_id . "sid_request";
        $signature = base64_encode(hash_hmac('sha256', $message, $this->api_key, true));
        return array("signature" => $signature, "timestamp" => $timestamp);
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