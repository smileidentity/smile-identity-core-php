<?php
spl_autoload_register(function ($class) {
    require_once($class . '.php');
});

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


function endsWith($haystack, $needle)
{
    $length = strlen($needle);
    if (!$length) {
        return true;
    }
    return substr($haystack, -$length) === $needle;
}


/**
 * @throws Exception
 */
function validatePartnerParams($partner_params)
{
    if ($partner_params == null) {
        throw new Exception("Please ensure that you send through partner params");
    }
    if (!array_key_exists("user_id", $partner_params)
        || !array_key_exists("job_id", $partner_params)
        || !array_key_exists("job_type", $partner_params)) {
        throw new Exception("Partner Parameter Arguments may not be null or empty");
    }
    if (gettype($partner_params["job_id"]) === "string") {
        throw new Exception("Please ensure job_id is a string");
    }
    if (gettype($partner_params["user_id"]) === "string") {
        throw new Exception("Please ensure user_id is a string");
    }
    if (gettype($partner_params["job_type"]) === "integer") {
        throw new Exception("Please ensure job_type is a integer");
    }
}


/**
 * @throws Exception
 */
function validateIdParams($id_params)
{
    if ($id_params == null) {
        throw new Exception("Please ensure that you send through partner params");
    }
    if ($id_params['entered']) {
        foreach (["country", "id_type", "id_number"] as $key) {
            $message = "Please make sure that $key is included in the id_info and has a value";
            if (!array_key_exists($key, $id_params)) {
                throw new Exception($message);
            }
            if ($id_params[$key] === null) {
                throw new Exception($message);
            }
        }
    }
}

/**
 * @throws Exception
 */
function validateImageParams($image_details)
{
    if ($image_details === null) {
        throw new Exception('Please ensure that you send through image details');
    }
    if (gettype($image_details) !== "array") {
        throw new Exception('Image details needs to be an array');
    }
    $has_selfie = false;
    foreach ($image_details as $item) {
        if (gettype($item) !== "array"
            || !array_key_exists("image_type_id", $item)
            || !array_key_exists("image", $item)) {
            throw new Exception("Image details content must to be an array with 'image_type_id' and 'image' has keys");
        }
        if ($item["image_type_id"] === 0 || $item["image_type_id"] === 2) {
            $has_selfie = true;
        }
    }
    if (!$has_selfie) {
        throw new Exception('You need to send through at least one selfie image');
    }
}

class SmileIdentityCore
{
    public Signature $sig_class;
    private string $partner_id;
    private string $api_key;
    private string $default_callback;
    private string $sid_server;

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
    }

    public function get_version(): string
    {
        return Config::VERSION;
    }

    /**
     * @return array
     */
    public function generate_sec_key(): array
    {
        return $this->sig_class->generate_sec_key();
    }

    /**
     * @param $filepath
     * @param $partner_params
     * @param $options
     * @return array|bool
     * @throws GuzzleException
     */
    public function submit_zip($filepath, $partner_params, $options)
    {
        $path_parts = pathinfo($filepath);
        $filename = $path_parts['filename'];
        $b = $this->sig_class->generate_sec_key();
        $sec_key = $b[0];
        $timestamp = $b[1];
        $response = false;
        $prep_upload_response_array = $this->call_prep_upload($sec_key, $timestamp, $partner_params, $filename, $options);
        $code = $prep_upload_response_array->code;
        if ($code == '2202') {
            $upload_url = $prep_upload_response_array->upload_url;
            $ref_id = $prep_upload_response_array->ref_id;
            $smile_job_id = $prep_upload_response_array->smile_job_id;
            $response = $this->upload_file($upload_url, $filepath);
        }
        $result = array(
            'success' => $response,
            "smile_job_id" => $smile_job_id
        );

        if ($result['success'] != false) {
            if ($options['return_job_status']) {
                for ($i = 1; $i <= $this->js_timeout; $i += DEFAULT_JOB_STATUS_SLEEP) {
                    sleep(DEFAULT_JOB_STATUS_SLEEP);
                    $response = $this->query_job_status($partner_params, $options);
                    if ($response['job_complete'] == true)
                        break;
                }
                $result = array_merge($result, $response);
            }
        }
        return $result;
    }

    /**
     * @param $partner_params
     * @param $options
     * @return array
     * @throws GuzzleException
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

        $client = new Client([
            'base_uri' => $this->sid_server,
            'timeout' => 5.0
        ]);
        $resp = $client->post('/job_status',
            [
                'content-type' => 'application/json',
                \GuzzleHttp\RequestOptions::JSON => $json_data
            ]
        );
        return json_decode($resp->getBody()->getContents(), true);
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

    /**
     * @throws GuzzleException
     * @throws Exception
     */
    public function submit_job($partner_params, $image_details, $id_info, $_options)
    {
        $options = array_merge(default_options, $_options);

        //TODO: add data validation
        validatePartnerParams($partner_params);
        validateIdParams($id_info);

        $job_type = $partner_params['job_type'];

        if ($job_type !== 5) {
            validateImageParams($image_details);
        }

        if ($job_type == 5) {
            $id_api = new IdApi($this->partner_id, $this->default_callback, $this->api_key, $this->sid_server);
            return $id_api->submit_job($partner_params, $id_info, $options);
        }

        if ($options['signature']) {
            $sec_params = $this->sig_class->generate_signature();
        } else {
            $sec_params = $this->sig_class->generate_sec_key();
        }

        $response_body = $this->call_prep_upload($partner_params, $options, $sec_params);
        $code = $response_body['code'];
        if ($code != '2202') {
            throw new Exception($response_body['error']);
        }

        $upload_url = $response_body['upload_url'];
        $smile_job_id = $response_body['smile_job_id'];
        $file_path = $this->generate_zip_file($response_body, $id_info, $image_details, $partner_params, $sec_params, $options);
        $response = $this->upload_file($upload_url, $file_path);

        $result = array('success' => false, "smile_job_id" => $smile_job_id);

        if ($options['return_job_status']) {
            for ($i = 1; $i <= 20; $i += 1) {
                sleep(DEFAULT_JOB_STATUS_SLEEP);
                $response = $this->query_job_status($partner_params, $options);
                if ($response['job_complete'] == true) {
                    $result['success'] = true;
                    break;
                }
            }
            $result = array_merge($result, $response);
        }
        return $result;
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
     * @return ResponseInterface
     * @throws GuzzleException
     */
    private function call_prep_upload($partner_params, $options, $sec_params): array
    {
        if ($options['optional_callback'] == null)
            $callback = $this->default_callback;
        else
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
        $client = new Client([
            'base_uri' => $this->sid_server,
            'timeout' => 5.0
        ]);
        try {
            $resp = $client->post('upload',
                [
                    'content-type' => 'application/json',
                    'body' => $json_data
                ]
            );
            return json_decode($resp->getBody()->getContents(), true);
        } catch (RequestException $e) {
            return json_decode($e->getResponse()->getBody()->getContents(), true);
        }
    }

    /**
     * @param $upload_url
     * @param $filename
     * @return array
     * @throws GuzzleException
     */
    private function upload_file($upload_url, $filename): array
    {
        $body = Psr7\Utils::tryFopen($filename, 'r');
        $client = new Client([]);
        $resp = $client->request('PUT', $upload_url, ['body' => $body, 'headers' => [
            'Content-Type' => 'application/zip',
        ]]);

        unlink($filename);
        return json_decode($resp->getBody()->getContents(), true);
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

        if ($options['optional_callback'] == "")
            $callback = $this->default_callback;
        else
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
}
