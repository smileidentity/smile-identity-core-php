<?php
spl_autoload_register(function($class) {
     require_once($class.'.php');
});

use ZipStream\Option\Archive as ArchiveOptions;

const VERSION = '1.1.0';
const SID_SERVERS = [
    'https://3eydmgh10d.execute-api.us-west-2.amazonaws.com/test',
    'https://la7am6gdm8.execute-api.us-west-2.amazonaws.com/prod'
];

class SmileIdentityCore
{

    public Signature $sig_class;
    private String $partner_id;
    private String $default_callback;
    private String $sid_server;

    /**
     * SmileIdentityCore constructor.
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

    public function get_version()
    {
        return VERSION;
    }

    /**
     * @param $timestamp
     * @return array
     */
    public function generate_sec_key(): array
    {
        return $this->sig_class->generate_sec_key();
    }

    /**
     * @param $sec_key
     * @param $timestamp
     * @param $partner_params
     * @param $filename
     * @param $options
     * @return mixed
     */
    private function call_prep_upload($sec_key, $timestamp, $partner_params, $filename, $options)
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

        $ch = curl_init($this->sid_server.'/upload');
        # Setup request to send json via POST.
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data );
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true );
        $prep_upload_response = curl_exec($ch);
        curl_close($ch);
        $result = json_decode($prep_upload_response);
        return $result;
    }

    /**
     * @param $upload_url
     * @param $filename
     * @return bool|string
     */
    private function upload_file($upload_url, $filename)
    {
        $ch = curl_init($upload_url);

        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_PUT, 1);
        curl_setopt($ch, CURLOPT_INFILESIZE, filesize($filename));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/zip'));
        $fh_res = fopen($filename, 'r');
        curl_setopt($ch, CURLOPT_INFILE, $fh_res);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
        $response = curl_exec($ch);
        fclose($fh_res);
        curl_close($ch);
        return $response;
    }

    /**
     * @param $upload_url
     * @param $stream
     * @param $file_len
     * @return bool|string
     */
    private function upload_stream($upload_url, $stream, $file_len)
    {
        $ch = curl_init($upload_url);

        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_PUT, 1);
        curl_setopt($ch, CURLOPT_INFILESIZE, $file_len);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/zip'));
        curl_setopt($ch, CURLOPT_INFILE, $stream);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }

    /**
     * @param $filepath
     * @param $partner_params
     * @param $options
     * @return array|bool|string
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
     * @return mixed
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

        $ch = curl_init($this->sid_server.'/job_status');
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data );
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true );
        $job_status_response = curl_exec($ch);
        $response = json_decode($job_status_response, true);
        curl_close($ch);
        return $response;
    }

    /**
     * @param $partner_params
     * @param $options
     * @return mixed
     */
    public function get_job_status($partner_params, $options)
    {
        return $this->query_job_status($partner_params, $options);
    }

    private function configure_image_payload($image_details)
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

    /**
     * @param $partner_params
     * @param $image_details
     * @param $id_info
     * @param $options
     * @return array|bool|string
     * @throws \ZipStream\Exception\FileNotFoundException
     * @throws \ZipStream\Exception\FileNotReadableException
     * @throws \ZipStream\Exception\OverflowException
     */
    public function submit_job($partner_params, $image_details, $id_info, $options)
    {
        $b = $this->sig_class->generate_sec_key();
        $sec_key = $b[0];
        $timestamp = $b[1];
        $response = false;
        $smile_job_id = '';

        $images = $this->configure_image_payload($image_details);

        $prep_upload_response_array = $this->call_prep_upload($sec_key, $timestamp, $partner_params, 'selfie.zip', $options);

        $code = $prep_upload_response_array->code;
        if($code == '2202')
        {
            $upload_url = $prep_upload_response_array->upload_url;
            $smile_job_id = $prep_upload_response_array->smile_job_id;

            $info = $this->configure_info_json($prep_upload_response_array, $id_info, $images, $partner_params, $sec_key, $timestamp, $options);
            $info_json = json_encode($info, JSON_PRETTY_PRINT);
            $tempStream = fopen('php://temp', 'rw+b');
            $zipStreamOptions = new ArchiveOptions();
            $zipStreamOptions->setOutputStream($tempStream);

            $zip = new \ZipStream\ZipStream('selfie.zip',$zipStreamOptions);
            $zip->addFile("info.json", $info_json);
            foreach ($image_details as $value)
            {
                if ($value['file_name'] != null)
                {
                    $zip->addFileFromPath(basename($value['file_name']),'./' . $value['file_name']);
                }
            }
            $zip->finish();
            $file_len = ftell($tempStream) ;
            rewind($tempStream);
            $response = $this->upload_stream($upload_url, $tempStream, $file_len);
            fclose($tempStream);
            $result = array(
                'success' => $response,
                "smile_job_id" => $smile_job_id
            );
        }
        else
        {
            $result = array(
                'success' => $response,
                "smile_job_id" => $prep_upload_response_array
            );
        }
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
}
