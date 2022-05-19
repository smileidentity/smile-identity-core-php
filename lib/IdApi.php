<?php
spl_autoload_register(function ($class) {
    require_once($class . '.php');
});

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;

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
     * @param string $partner_id the provided partner ID string
     * @param string $default_callback
     * @param string $api_key the partner-provided API key
     * @param string $sid_server an integer value corresponding to the chosen server
     * 0 for test/sandbox
     * 1 for production
     * @throws Exception
     */
    public function __construct($partner_id, $default_callback, $api_key, $sid_server)
    {
        $this->partner_id = $partner_id;
        $this->default_callback = $default_callback;
        $this->sig_class = new Signature($partner_id, $api_key);
        if (strlen($sid_server) == 1) {
            if (intval($sid_server) < 2) {
                $this->sid_server = Config::SID_SERVERS[intval($sid_server)];
            } else {
                throw new Exception("Invalid server selected");
            }
        } else {
            $this->sid_server = $sid_server;
        }
    }
    
    /**
     * @param Client $client
     */
    public function setClient(Client $client): void
    {
        $this->client = $client;
    }

    /**
     * Submits a job with specified partner parameters and ID information
     * @param array $partner_params a key-value pair object containing partner's specified parameters
     * @param array $id_info a key-value pair object containing user's specified ID information
     * @param array $options a key-value pair object containing additional, optional parameters
     * @return ResponseInterface
     * @throws GuzzleException
     * @throws Exception
     */
    public function submit_job($partner_params, $id_info, $options): ResponseInterface
    {
        $user_async = array_value_by_key("user_async", $options);
        $sec_params = $this->sig_class->generate_signature();

        $data = array(
            'language' => 'php',
            'callback_url' => $this->default_callback,
            'partner_params' => $partner_params,
            'partner_id' => $this->partner_id,
            'source_sdk' => Config::SDK_CLIENT,
            'source_sdk_version' => Config::VERSION
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