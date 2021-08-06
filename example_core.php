<?php

// Autoload the dependencies
require 'vendor/autoload.php';
include 'SmileIdentityCore.php';

$partner_id = '<Put your 3 digit partner ID here>';
$default_callback = '<Put your default callback url here>';
// You can download your API key from the Smile Identity portal.
// NOTE: The sandbox and production servers use different API keys.
$api_key = '<Put your base64 encoded API key here>';
// Use '0' for the sandbox (test) server, use '1' for production server
$sid_server = '0';

$sid_core = new SmileIdentityCore(
    $partner_id,
    $default_callback,
    $api_key,
    $sid_server
);

// Create required tracking parameters
// Every communication between your server and the Smile Identity servers contain these parameters.
// Use them to match up the job results with the job and user you submitted.
$partner_params = array(
    // String containing a unique job ID for this job. You can place your own here or use uniqid or any other UUID generator.
    'job_id' => '<put your unique ID for the user here>',
    // String containing a unique user ID for this job. You can place your own here or use uniqid or any other UUID generator.
    'user_id' => '<put unique job ID here',
    // Job Type Integer
    // 1 for jobs that compare a selfie to an ID whether ID Card or 3rd party issuer
    // 2 for authenticating a selfie against a previously registered user
    // 4 for registering a user with just a selfie
    // 8 for updating an registered photo
    'job_type' => <1 | 2 | 4 | 8>,
     // You can add as many key value pairs as you line but all MUST be strings.
    'optional_info' => 'PHP Test Data'
    );

// Create options
$options = array(
    // Per job callback. If blank the default_callback is used
    'optional_callback' => '',
    // After a job is submitted, you can have the code wait for a response from the job_status API. Default timeout is 20 seconds
    'return_job_status' => <true | false>,
    // Returns, if true, all the same JSON sent to the callback
    'return_history' => <true | false>,
    // If you want signed links to the images used in processing the job to be returned
    'return_image_links' => <true | false>
    );

// Create image list
// image_type_id Integer
// 0 - Selfie image jpg or png
// 1 - ID card image jpg or png
// 2 - Selfie image jpg or png base64 encoded
// 3 - ID card image jpg or png base 64 encoded
$selfie_filename = 'tmp/selife.jpg'; // Path to selfie image file
$id_card_filename = 'tmp/idcard.jpg'; // Path to idcard image file
$selfie_image_detail = array(
    'image_type_id' => 0, // Selfie image jpg or png
    'image' => $selfie_filename
    );
// ID card image can be omitted if selfie comparison to issuer image is desired
$id_card_image_detail = array(
    'image_type_id' => 1, // ID card image jpg or png
    'image' => $id_card_filename
    );
$image_details = array(
    $selfie_image_detail,
    $id_card_image_detail
    );

// Create ID number info
// Only required fields need to be filled in. The rest should be blank strings
// Set 'entered' to 'false' if no issuer lookup is needed
$id_info = array(
    'first_name' => '<name>',
    'middle_name' => '<middle>',
    'last_name' => '<surname>',
    'country' => '<country code>',
    'id_type' => '<id type>',
    'id_number' => '<valid id number>', // Always required
    'dob' => '<DOB in ISO 8601 format>', // yyyy-mm-dd
    'entered' => '<true | false>' // MUST BE A STRING
    );

// submit_job returns an array with at least a Boolean using the key "success" and the Smile Identity job number.
// If options.return_job_status is true it will add to the array the returned job_status information.
$result = $sid_core->submit_job($partner_params, $image_details, $id_info, $options);


//-------------------------------------------------------------------------------------------------------------
// Smile ID ID Verification API usage
//

$sid_idapi = new IdApi(
    $partner_id,
    $default_callback, // Used if $use_async is true otherwise should be ""
    $api_key,
    $sid_server
);

// If use_async is false $result contains the returned ID information
// If true then the ID information will be sent to the callback specified - >> RECOMMENDED <<
$use_async =  false;

// Create required tracking parameters
// Every communication between your server and the Smile Identity servers contain these parameters.
// Use them to match up the job results with the job and user you submitted.
$partner_params = array(
    // String containing a unique job ID for this job. You can place your own here or use uniqid or any other UUID generator.
    'job_id' => '<put your unique ID for the user here>',
    // String containing a unique user ID for this job. You can place your own here or use uniqid or any other UUID generator.
    'user_id' => '<put unique job ID here',
    // Job Type Integer
    'job_type' => 5,
     // You can add as many key value pairs as you line but all MUST be strings.
    'optional_info' => 'PHP Test Data'
    );

// Create ID number info
// Only required fields need to be filled in. The rest should be blank strings
// Set 'entered' to 'false' if no issuer lookup is needed
$id_info = array(
    'first_name' => '<name>',
    'middle_name' => '<middle>',
    'last_name' => '<surname>',
    'country' => '<country code>',
    'id_type' => '<id type>',
    'id_number' => '<valid id number>', // Always required
    'dob' => '<DOB in ISO 8601 format>', // yyyy-mm-dd
    'entered' => '<true | false>' // MUST BE A STRING
    );


//
$result = $sid_idapi->submit_job($partner_params, $id_info, $use_async);



?>
