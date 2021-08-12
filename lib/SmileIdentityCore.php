<?php
spl_autoload_register(function($class) {
     require_once($class.'.php');
});

use \GuzzleHttp\Client;
use \GuzzleHttp\Exception\GuzzleException;
use \Psr\Http\Message\ResponseInterface;

const VERSION = '1.1.0';
const DEFAULT_JOB_STATUS_SLEEP = 2;

class SmileIdentityCore
{
    public Signature $sig_class;
    private String $partner_id;
    private String $default_callback;
    private String $sid_server;

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
        $this->default_callback = $default_callback;
        $this->sig_class = new Signature($api_key, $partner_id);
        if(strlen($sid_server) == 1) {
            if(intval($sid_server) < 2) {
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
        if($code == '2202')
        {
            $upload_url = $prep_upload_response_array->upload_url;
            $ref_id = $prep_upload_response_array->ref_id;
            $smile_job_id = $prep_upload_response_array->smile_job_id;
            $response = $this->upload_file($upload_url, $filepath);
        }
        $result = array(
            'success' => $response,
            "smile_job_id" => $smile_job_id
        );

        if($result['success'] != false)
        {
            if($options['return_job_status'])
            {
                for ($i = 1; $i <= $this->js_timeout; $i += DEFAULT_JOB_STATUS_SLEEP)
                {
                    sleep(DEFAULT_JOB_STATUS_SLEEP);
                    $response = $this->query_job_status($partner_params, $options);
                    if($response['job_complete'] == true)
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
     * @return ResponseInterface
     * @throws GuzzleException
     */
    public function query_job_status($partner_params, $options)
    {
        $b = $this->sig_class->generate_sec_key();
        $sec_key = $b[0];
        $timestamp = $b[1];

        $data = array(
            'sec_key' => $sec_key,
            'timestamp' => $timestamp,
            'user_id' => $partner_params['user_id'],
            'job_id' => $partner_params['job_id'],
            'partner_id' => $this->partner_id,
            'image_links' => $options['return_image_links'],
            'history' => $options['return_history'],
        );

        $json_data = json_encode($data,JSON_PRETTY_PRINT);

        $client = new Client([
            'base_uri' => $this->sid_server,
            'timeout'  => 5.0
        ]);
        return $client->post('/job_status',
            [
                'content-type' => 'application/json',
                \GuzzleHttp\RequestOptions::JSON => $json_data
            ]
        );
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
        $images = array();
        foreach ($image_details as $value)
        {
            $image_detail_p = array(
                'image_type_id' => $value['image_type_id'],
                'image' => '',
                'file_name' => ''
            );
            if ($value['file_name'] != null)
            {
                $image_detail_p['file_name'] = basename($value['file_name']);
            }
            else
            {
                $image_detail_p['image'] = $value['image'];
            }
            $images[] = $image_detail_p;

        }
        return $images;
    }

    /**
     * @param $sec_key
     * @param $timestamp
     * @param $partner_params
     * @param $filename
     * @param $options
     * @return ResponseInterface
     * @throws GuzzleException
     */
    private function call_prep_upload($sec_key, $timestamp, $partner_params, $filename, $options): ResponseInterface
    {
        if($options['optional_callback'] == null)
            $callback = $this->default_callback;
        else
            $callback = $options['optional_callback'];

        $data = array(
            'callback_url' => $callback,
            'file_name' => $filename,
            'model_parameters' => '',
            'partner_params' => $partner_params,
            'sec_key' => $sec_key,
            'timestamp' => $timestamp,
            'smile_client_id' => $this->partner_id
        );

        $json_data = json_encode($data,JSON_PRETTY_PRINT);


        $client = new Client([
            'base_uri' => $this->sid_server,
            'timeout'  => 5.0
        ]);
        return $client->post('/upload',
            [
                'content-type' => 'application/json',
                'body' => $json_data
            ]
        );
    }

    /**
     * @param $upload_url
     * @param $filename
     * @return ResponseInterface
     * @throws GuzzleException
     */
    private function upload_file($upload_url, $filename): ResponseInterface
    {
        $client = new Client([
            'timeout'  => 5.0
        ]);
        $body = fopen($filename, 'r');
        $response = $client->request('POST', $upload_url, ['body' => $body]);
        fclose($body);
        return $response;
    }

    /**
     * @param $prep_upload_response_array
     * @param $id_info
     * @param $images
     * @param $partner_params
     * @param $sec_key
     * @param $timestamp
     * @param $options
     * @return array
     */
    private function configure_info_json($prep_upload_response_array, $id_info, $images, $partner_params, $sec_key, $timestamp, $options)
    {

        if($options['optional_callback'] == "")
            $callback = $this->default_callback;
        else
            $callback = $options['optional_callback'];
        $info = array(
            "package_information" => array(
                "apiVersion" => array(
                    "buildNumber" => 0,
                    "majorVersion" => 2,
                    "minorVersion" => 0
                ),
                "language" => "php"
            ),
            "misc_information"=> array(
                "sec_key" => $sec_key,
                "retry" => "false",
                "partner_params" => $partner_params,
                "timestamp" => $timestamp,
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
            ),
            "id_info" => $id_info,
            "images" => $images,
            "server_information" => $prep_upload_response_array
        );
        return $info;
    }
}
