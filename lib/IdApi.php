<?php
spl_autoload_register(function ($class) {
    require_once($class . '.php');
});

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;

include 'utils.php';

require 'vendor/autoload.php';

class IdApi
{
    const SID_SERVERS = [
        'https://testapi.smileidentity.com/v1',
        'https://api.smileidentity.com/v1'
    ];
    public Signature $sig_class;
    private Client $client;
    private string $partner_id;
    private string $default_callback;
    private string $sid_server;

    /**
     * IdApi constructor.
     * @param $partner_id
     * @param $default_callback
     * @param $api_key ,
     * @param $sid_server
     * @throws Exception
     */
    public function __construct($partner_id, $default_callback, $api_key, $sid_server)
    {
        $this->partner_id = $partner_id;
        $this->default_callback = $default_callback;
        $this->sig_class = new Signature($api_key, $partner_id);
        if (strlen($sid_server) == 1) {
            if (intval($sid_server) < 2) {
                $this->sid_server = Config::SID_SERVERS[intval($sid_server)];
            } else {
                throw new Exception("Invalid server selected");
            }
        } else {
            $this->sid_server = $sid_server;
        }
        $this->client = new Client(['base_uri' => $this->sid_server]);
    }
    
    /**
     * @param Client $client
     */
    public function setClient(Client $client): void
    {
        $this->client = $client;
    }

    /**
     * @param $partner_params
     * @param $id_info
     * @param $use_async
     * @param $options
     * @return ResponseInterface
     * @throws GuzzleException
     * @throws Exception
     */
    public function submit_job($partner_params, $id_info, $options): ResponseInterface
    {
        $user_async = array_value_by_key("user_async", $options);
        $signature = array_value_by_key("signature", $options);

        if ($signature) {
            $sec_params = $this->sig_class->generate_signature();
        } else {
            $sec_params = $this->sig_class->generate_sec_key();
        }

        $data = array(
            'language' => 'php',
            'callback_url' => $this->default_callback,
            'partner_params' => $partner_params,
            'partner_id' => $this->partner_id
        );
        $data = array_merge($data, $id_info, $sec_params);
        $json_data = json_encode($data, JSON_PRETTY_PRINT);
        $client = is_null($this->client) ? new Client(['base_uri' => $this->sid_server, 'timeout' => 5.0]) : $this->client;
        $url = $user_async ? 'async_id_verification' : 'id_verification';
        return $client->post($url, [
            'content-type' => 'application/json',
            'body' => $json_data
        ]);
    }
}
