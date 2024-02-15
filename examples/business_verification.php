<?php

use SmileIdentity\JobType;
use SmileIdentity\SmileIdentityCore;

// Autoload the dependencies
require 'vendor/autoload.php';

// See https://docs.usesmileid.com/products/for-businesses-kyb/business-verification for
// more information on business verification

$partner_id = '<Put your partner ID here>';
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
  'job_type' => JobType::BUSINESS_VERIFICATION,
);

// Create ID number info
$id_info = array(
  'country' => '<2-letter country code>',
  'id_type' => '<BASIC_BUSINESS_REGISTRATION | BUSINESS_REGISTRATION | TAX_INFORMATION>',
  'id_number' => '<valid id number>',
  // The business incorporation type bn - business name, co - private/public limited, it - incorporated trustees
  // Only required for BASIC_BUSINESS_REGISTRATION and BUSINESS_REGISTRATION in Nigeria
  // Postal address of business. Only Required for BUSINESS_REGISTRATION in Kenya
  'business_type' => 'co',
  'postal_address' => 'postal address',
  // Postal code of business. Only Required for BUSINESS_REGISTRATION in Kenya
  'postal_code' => '<true | false>'
);


// submit_job returns an array with at least a Boolean using the key "success" and the Smile Identity job number.
// If options.return_job_status is true it will add to the array the returned job_status information.
$result = $sid_core->submit_job($partner_params, [], $id_info, array());
