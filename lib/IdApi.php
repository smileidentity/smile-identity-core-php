<?php

namespace sid;

require 'config.php';
require 'Signature.php';
require 'vendor/autoload.php';

class IdApi
{

    public $sig_class;
    private $partner_id;
    private $default_callback;
    private $sid_server;
    private $js_timeout = DEFAULT_JOB_STATUS_TIMEOUT;


    public function initialize($i_partner_id, $i_default_callback, $i_api_key, $i_sid_server)
    {
        $this->partner_id = $i_partner_id;
        $this->default_callback = $i_default_callback;
        $this->sig_class = new Signature($i_partner_id, $i_api_key);
        if(strlen($i_sid_server) == 1)
            if(intval($i_sid_server) < 2)
                $this->sid_server = SID_SERVERS[intval($i_sid_server)];
            else
                throw new Exception("Invalid server selected");
        else
            $this->sid_server = $i_sid_server;
    }


    public function submit_job($partner_params, $id_info, $use_async)
    {
        $b = $this->sig_class->generate_sec_key();
        $sec_key = $b[0];
        $timestamp = $b[1];
        $response = false;
        $smile_job_id = '';

        $data = array(
            'language' => 'php',
            'callback_url' => $this->default_callback,
            'partner_params' => $partner_params,
            'sec_key' => $sec_key,
            'timestamp' => $timestamp,
            'partner_id' => $this->partner_id
        );
        $data = array_merge($data, $id_info);


        $json_data = json_encode($data, JSON_PRETTY_PRINT);

        if($use_async)
            $ch = curl_init($this->sid_server.'/async_id_verification');
        else
            $ch = curl_init($this->sid_server.'/id_verification');
        # Setup request to send json via POST.
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data );
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true );
        $response = curl_exec($ch);
        curl_close($ch);
        $result = json_decode($response);

        return $result;

    }
}
