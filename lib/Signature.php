<?php

namespace sid;

class Signature
{
    private $api_key;
    private $partner_id;

    function __construct($api_key, $partner_id)
    {
        $this->api_key = $api_key;
        $this->partner_id = $partner_id;   
    }

    function generate_sec_key()
    {
        $timestamp = time();
        $plaintext = intval($this->partner_id) . ":" . $timestamp;
        $hash_signature = hash('sha256', $plaintext);
        openssl_public_encrypt($hash_signature, $sec_key, base64_decode($this->api_key), OPENSSL_PKCS1_PADDING);
        $sec_key = base64_encode($sec_key);
        $sec_key = $sec_key . "|" . $hash_signature;
        return array($sec_key, $timestamp);
    }

    function confirm_sec_key($timestamp, $sec_key)
    {
        $sec_key_exploded = explode("|", $sec_key);
        $encrypted = base64_decode($sec_key_exploded[0]);
        $hash_signature = $sec_key_exploded[1];
        $decrypted = '';
        openssl_public_decrypt($encrypted, $decrypted, base64_decode($this->api_key), OPENSSL_PKCS1_PADDING);
        return $hash_signature == $decrypted;
    }
}
