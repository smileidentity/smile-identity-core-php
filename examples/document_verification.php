<?php

// Autoload the dependencies
require 'vendor/autoload.php';
include '/Users/ordgen/code/php/smile-identity-core-php/lib/SmileIdentityCore.php';

// See https://docs.smileidentity.com/server-to-server/php/products/document-verification for
// how to setup and retrieve configuation values for the SmileIdentityCore class.

$partner_id = '2334';
$default_callback = 'https://callback.smileidentity.co';
// You can download your API key from the Smile Identity portal.
// NOTE: The sandbox and production servers use different API keys.
// $api_key = '1d6b6b72-f9a6-4da3-ba0c-414247aa6f6d'; // dev prod (manually change the api url to use dev instead of sandbox)
// $api_key = '6adcd89e-f97c-4553-abfa-87ef26b424dc'; // test
$api_key = 'af8f8da1-6625-4e26-b1d6-9e949f4952ab'; // true prod

// Use '0' for the sandbox (test) server, use '1' for production server
$sid_server = '1';

$sid_core = new SmileIdentityCore(
    $partner_id,
    $default_callback,
    $api_key,
    $sid_server
);

$user_id = uniqid();
$job_id = uniqid();

// Create required tracking parameters
// Every communication between your server and the Smile Identity servers contain these parameters.
// Use them to match up the job results with the job and user you submitted.
$partner_params = array(
  'job_id' => $job_id,
  'user_id' => $user_id,
  'job_type' => JobType::ENHANCED_DOCUMENT_VERIFICATION,
);

// The ID Document Information
$id_info = array(
  'country' => 'GH',
  'id_type' => 'PASSPORT' // id_type is optional. If a job is submitted without id_type and the machine can't classify the document, we will reject the job
);

// Create image list
// image_type_id Integer
// 0 - Selfie image jpg or png (if you have the full path of the selfie)
// 2 - Selfie image jpg or png base64 encoded (if you have the base64image string of the selfie)
// 4 - Liveness image jpg or png (if you have the full path of the liveness image)
// 6 - Liveness image jpg or png base64 encoded (if you have the base64image string of the liveness image)
// 1 - Front of ID document image jpg or png (if you have the full path of the selfie)
// 3 - Front of ID document image jpg or png base64 encoded (if you have the base64image string of the selfie)
// 5 - Back of ID document image jpg or png (if you have the full path of the selfie)
// 7 - Back of ID document image jpg or png base64 encoded (if you have the base64image string of the selfie)
$selfie_filename = '/Users/ordgen/Documents/p/profile_pic.jpeg'; // Path to selfie image file
$id_card_filename = '/Users/ordgen/Documents/p/passport.jpg'; // Path to idcard image file
$selfie_image_detail = array(
    'image_type_id' => ImageType::SELFIE_FILE, // Selfie image jpg or png
    'image' => $selfie_filename
);
// ID card image can be omitted if selfie comparison to issuer image is desired
$id_card_image_detail = array(
    'image_type_id' => ImageType::ID_CARD_FILE, // ID card image jpg or png
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
  'return_job_status' => true,
  // Set to true to receive all of the updates you would otherwise have received in your callback
  // as opposed to only the final result. You must set return_job_status to true to use this flag.
  'return_history' => false,
  // Set to true to receive links to the selfie and the photo
  // it was compared to. You must set return_job_status to true to use this flag.
  'return_image_links' => true
);

// submit_job returns an array with at least a Boolean using the key "success" and the Smile Identity job number.
// If options.return_job_status is true it will add to the array the returned job_status information.
$result = $sid_core->submit_job($partner_params, $image_details, $id_info, $options);
print_r("USER_ID: $user_id");
print_r("JOB_ID: $job_id");
print_r($result);
