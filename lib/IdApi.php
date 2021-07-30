<?php
spl_autoload_register(function($class) {
    require_once($class.'.php');
});

require 'vendor/autoload.php';

class IdApi
{

    public Signature $sig_class;
    private String $partner_id;
    private String $default_callback;
    private String $sid_server;

    /**
     * IdApi constructor.
     * @param $partner_id
     * @param $default_callback
     * @param $api_key
     * @param $sid_server
     * @throws Exception
     */
    public function __construct($partner_id, $default_callback, $api_key, $sid_server)
    {
        $this->partner_id = $partner_id;
        $this->default_callback = $default_callback;
        $this->sig_class = new Signature($api_key, $partner_id);
        if(strlen($sid_server) == 1) {
            if(intval($sid_server) < 2) {
                $this->sid_server = SID_SERVERS[intval($sid_server)];
            } else {
                throw new Exception("Invalid server selected");
            }
        } else {
            $this->sid_server = $sid_server;
        }
    }

    /**
     * @param $partner_params
     * @param $id_info
     * @param $use_async
     * @return mixed
     */
    public function submit_job($partner_params, $id_info, $use_async)
    {
        $b = $this->sig_class->generate_sec_key($timestamp = null);
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
        return json_decode($response);

    }
}
