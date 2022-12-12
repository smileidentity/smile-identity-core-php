<?php

// Autoload the dependencies
require '/absolute/path/to/vendor/autoload.php';
include '/absolute/path/to/lib/SmileIdentityCore.php';

// See https://docs.smileidentity.com/server-to-server/ruby/products/smartselfie-tm-authentication for
// how to setup and retrieve configuation values for the SmileIdentityCore class.

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
  'job_id' => '<put your unique ID for the user here>',
  'user_id' => '<put unique job ID here',
  'job_type' => 2,
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

// Create options
$options = array(
  // Per job callback. If blank the default_callback is used
  'optional_callback' => '',
  // Set to true if you want to get the job result in sync (in addition to the result been sent
  // to your callback). If set to false, result is sent to callback url only.
  'return_job_status' => '<true | false>',
  // Set to true to receive all of the updates you would otherwise have received in your callback
  // as opposed to only the final result. You must set return_job_status to true to use this flag.
  'return_history' => '<true | false>',
  // Set to true to receive links to the selfie and the photo
  // it was compared to. You must set return_job_status to true to use this flag.
  'return_image_links' => '<true | false>'
);

// submit_job returns an array with at least a Boolean using the key "success" and the Smile Identity job number.
// If options.return_job_status is true it will add to the array the returned job_status information.
$result = $sid_core->submit_job($partner_params, $image_details, array(), $options);
