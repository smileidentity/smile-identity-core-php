<?php
spl_autoload_register(function ($class) {
    require_once($class . '.php');
});

include 'utils.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7;
use Psr\Http\Message\ResponseInterface;


const VERSION = '1.1.0';
const DEFAULT_JOB_STATUS_SLEEP = 2;
const default_options = array(
    'optional_callback' => '',
    'return_job_status' => false,
    'return_history' => false,
    'return_image_links' => false,
    'signature' => false,
    'user_async' => false,
);

class SmileIdentityCore
{
    public Signature $sig_class;
    private string $partner_id;
    private string $api_key;
    private string $default_callback;
    private string $sid_server;
    private Client $client;

    /**
     * WebApi constructor.
     * @param $partner_id
     * @param $default_callback
     * @param $api_key
     * @param $sid_server
     * @throws Exception
     */
    public function __construct($partner_id, $default_callback, $api_key, $sid_server)
    {
        $this->partner_id = $partner_id;
        $this->api_key = $api_key;
        $this->default_callback = $default_callback;
        $this->sig_class = new Signature($api_key, $partner_id);
        if (strlen($sid_server) == 1) {
            if (intval($sid_server) < 2) {
                $this->sid_server = Config::SID_SERVERS[intval($sid_server)];
            } else {
                throw new \Exception("Invalid server selected");
            }
        } else {
            $this->sid_server = $sid_server;
        }
        $this->client = new Client(['base_uri' => $this->sid_server]);
    }

    public function get_version(): string
    {
        return Config::VERSION;
    }

    /**
     * @param bool $use_signature
     * @return array
     */
    public function generate_sec_key(bool $use_signature): array
    {
        if ($use_signature){
            return $this->sig_class->generate_signature();
        }
        return $this->sig_class->generate_sec_key();
    }

    /**
     * @throws GuzzleException
     * @throws Exception
     */
    public function submit_job($partner_params, $image_details, $id_info, $_options)
    {
        $options = $this->getOptions($_options);

        //TODO: add data validation
        validatePartnerParams($partner_params);
        validateIdParams($id_info);

        $job_type = $partner_params['job_type'];

        if ($job_type == 5) {
            $id_api = new IdApi($this->partner_id, $this->default_callback, $this->api_key, $this->sid_server);
            return $id_api->submit_job($partner_params, $id_info, $options);
        }
        validateImageParams($image_details);
        validateOptions($options);

        if ($options['signature']) {
            $sec_params = $this->sig_class->generate_signature();
        } else {
            $sec_params = $this->sig_class->generate_sec_key();
        }

        $response_body = $this->call_prep_upload($partner_params, $options, $sec_params);
        $code = array_value_by_key('code', $response_body);
        if ($code != '2202') {
            $message = array_value_by_key('error', $response_body);
            if (!$message) {
                $message = array_value_by_key('message', $response_body);
            }
            throw new Exception($message);
        }

        $upload_url = $response_body['upload_url'];
        $smile_job_id = $response_body['smile_job_id'];
        $file_path = $this->generate_zip_file($response_body, $id_info, $image_details, $partner_params, $sec_params, $options);
        $response = $this->upload_file($upload_url, $file_path);

        if ($response["statusCode"] != 200) {
            throw new Exception("Failed to upload zip. status code: {$response["statusCode"]}");
        }

        if ($options['return_job_status']) {
            $result = $this->poll_job_status($partner_params, $options);
        } else {
            $result = array('success' => true, "smile_job_id" => $smile_job_id);
        }
        return $result;
    }

    /**
     * @param $partner_params
     * @param $options
     * @return array
     * @throws GuzzleException
     * @throws Exception
     */
    public function query_job_status($partner_params, $options): array
    {
        if ($options['signature']) {
            $sec_params = $this->sig_class->generate_signature();
        } else {
            $sec_params = $this->sig_class->generate_sec_key();
        }

        $data = array(
            'user_id' => $partner_params['user_id'],
            'job_id' => $partner_params['job_id'],
            'partner_id' => $this->partner_id,
            'image_links' => $options['return_image_links'],
            'history' => $options['return_history'],
        );
        $data = array_merge($data, $sec_params);

        $json_data = json_encode($data, JSON_PRETTY_PRINT);

        $client = $this->getClient();
        $resp = $client->post('job_status', ['content-type' => 'application/json', 'body' => $json_data]);
        $result = json_decode($resp->getBody()->getContents(), true);

        if ($options['signature']) {
            $valid = $this->sig_class->confirm_signature($result['timestamp'], $result['signature']);
        } else {
            $valid = $this->sig_class->confirm_sec_key($result['signature']);
        }

        if (!$valid) {
            throw new Exception("Unable to confirm validity of the job_status response");
        }

        return $result;
    }

    /**
     * @param $partner_params
     * @param $options
     * @return mixed
     * @throws GuzzleException
     */
    public function get_job_status($partner_params, $options)
    {
        return $this->query_job_status($partner_params, $options);
    }

    private function configure_image_payload($image_details): array
    {
        $images_json = array();
        foreach ($image_details as $image) {
            if (endsWith($image["image"], '.png')
                || endsWith($image["image"], '.jpg')
                || endsWith($image["image"], '.jpeg')) {
                array_push($images_json, array(
                    "image_type_id" => $image["image_type_id"],
                    "image" => "",
                    "file_name" => basename($image["image"]),
                ));
            } else {
                array_push($images_json, array(
                    "image_type_id" => $image["image_type_id"],
                    "file_name" => "",
                    "image" => $image["image"],
                ));
            }
        }
        return $images_json;
    }

    /**
     * @param $partner_params
     * @param $options
     * @param $sec_params
     * @return array
     * @throws GuzzleException
     */
    private function call_prep_upload($partner_params, $options, $sec_params): array
    {
        $callback = $options['optional_callback'];

        $data = array(
            'callback_url' => $callback,
            'file_name' => 'selfie.zip',
            'model_parameters' => '',
            'partner_params' => $partner_params,
            'smile_client_id' => $this->partner_id,
        );

        $data = array_merge($sec_params, $data);

        $json_data = json_encode($data, JSON_PRETTY_PRINT);
        try {
            $resp = $this->client->post('upload',
                [
                    'content-type' => 'application/json',
                    'body' => $json_data
                ]
            );
            return json_decode($resp->getBody()->getContents(), true);
        } catch (RequestException $e) {
            $resp = $e->getResponse();
            $result = json_decode($resp->getBody()->getContents(), true);
            $result['statusCode'] = $resp->getStatusCode();
            return $result;
        }
    }

    /**
     * @param $upload_url
     * @param $filename
     * @throws GuzzleException
     */
    private function upload_file($upload_url, $filename)
    {
        $body = Psr7\Utils::tryFopen($filename, 'r');
        $resp = $this->getClient()->request('PUT', $upload_url, ['body' => $body, 'headers' => [
            'Content-Type' => 'application/zip',
        ]]);

        unlink($filename);
        $result = json_decode($resp->getBody()->getContents(), true);
        $result["statusCode"] = $resp->getStatusCode();
        return $result;
    }

    /**
     * @param $prep_upload_response_array
     * @param $id_info
     * @param $images
     * @param $partner_params
     * @param $sec_param
     * @param $options
     * @return array
     */
    private function configure_info_json($prep_upload_response_array, $id_info, $images, $partner_params, $sec_param, $options)
    {
        $callback = $options['optional_callback'];
        $misc = array(
            "retry" => "false",
            "partner_params" => $partner_params,
            "file_name" => "selfie.zip",
            "smile_client_id" => $this->partner_id,
            "callback_url" => $callback,
            "userData" => array(
                "isVerifiedProcess" => false,
                "name" => "",
                "fbUserID" => "",
                "firstName" => "",
                "lastName" => "",
                "gender" => "",
                "email" => "",
                "phone" => "",
                "countryCode" => "+",
                "countryName" => ""
            )
        );
        $misc = array_merge($misc, $sec_param);

        return array(
            "package_information" => array(
                "apiVersion" => array(
                    "buildNumber" => 0,
                    "majorVersion" => 2,
                    "minorVersion" => 0
                ),
                "language" => "php"
            ),
            "misc_information" => $misc,
            "id_info" => $id_info,
            "images" => $this->configure_image_payload($images),
            "server_information" => $prep_upload_response_array
        );
    }

    /**
     */
    private function generate_zip_file($response_body, $id_info, $images_info, $partner_params, $sec_param, $options): string
    {

        $info_json = $this->configure_info_json($response_body, $id_info, $images_info, $partner_params, $sec_param, $options);
        $file = tempnam(sys_get_temp_dir(), "selfie");
        $zip = new ZipArchive();

        // Zip will open and overwrite the file, rather than try to read it.
        $res = $zip->open($file, ZipArchive::CREATE);
        if ($res === TRUE) {
            $zip->addFromString('info.json', json_encode($info_json, JSON_PRETTY_PRINT));
            foreach ($images_info as $image) {
                if (endsWith($image["image"], '.png')
                    || endsWith($image["image"], '.jpg')
                    || endsWith($image["image"], '.jpeg')) {
                    $zip->addFile($image["image"], basename($image["image"]));
                }
            }
            $zip->close();
        }
        return $file;
    }

    /**
     * @param $_options
     * @return array
     */
    private function getOptions($_options): array
    {
        $options = array_merge(default_options, $_options);
        if ($options['optional_callback'] == null || strlen($options["optional_callback"]) == 0) {
            $options["optional_callback"] = $this->default_callback;
        }
        return $options;
    }

    /**
     * @param $partner_params
     * @param array $options
     * @param array $response
     * @param $smile_job_id
     * @return array
     * @throws GuzzleException
     */
    private function poll_job_status($partner_params, array $options): array
    {
        for ($i = 1; $i <= 20; $i += 1) {
            sleep(DEFAULT_JOB_STATUS_SLEEP);
            $result = $this->query_job_status($partner_params, $options);
            if ($result['job_complete'] == true) {
                $result['success'] = true;
                break;
            }
        }
        return $result;
    }

    /**
     * @return Client
     */
    public function getClient(): Client
    {
        return $this->client;
    }

    /**
     * @param Client $client
     */
    public function setClient(Client $client): void
    {
        $this->client = $client;
    }
}
