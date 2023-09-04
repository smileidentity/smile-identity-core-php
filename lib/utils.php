<?php

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
    if (gettype($partner_params["job_id"]) !== "string") {
        throw new Exception("Please ensure job_id is a string");
    }
    if (gettype($partner_params["user_id"]) !== "string") {
        throw new Exception("Please ensure user_id is a string");
    }
    if (gettype($partner_params["job_type"]) !== "integer") {
        throw new Exception("Please ensure job_type is a integer");
    }
}

/**
 * @throws Exception
 */
function validateIdParams($id_params, $job_type)
{
    if ($id_params == null) {
        throw new Exception("Please ensure that you send through partner params");
    }

    $not_doc_or_biz_verification = !in_array($job_type, array(JobType::DOCUMENT_VERIFICATION, JobType::BUSINESS_VERIFICATION));
    if ($not_doc_or_biz_verification && (!key_exists("entered", $id_params) || strtolower("{$id_params['entered']}") !== "true")) {
        return;
    }

    if ($job_type == JobType::DOCUMENT_VERIFICATION) {
        $required_fields = ["country"];
    } else {
        $required_fields = ["country", "id_number", "id_type"];
    }
    foreach ($required_fields as $key) {
        $message = "Please make sure that $key is included in the id_info and has a value";
        if (!array_key_exists($key, $id_params)) {
            throw new Exception($message);
        }
        if ($id_params[$key] === null || $id_params[$key] === "") {
            throw new Exception($message);
        }
    }

    if ($job_type == JobType::BUSINESS_VERIFICATION && !in_array($id_params["id_type"], BusinessVerificationType::ALL)) {
        $expected_types = implode(", ", BusinessVerificationType::ALL);
        throw new InvalidArgumentException("id_type must be one of $expected_types");
    }
}

/**
 * @throws Exception
 */
function validateImageParams($image_details, $job_type, $use_enrolled_image)
{
    if ($image_details === null) {
        throw new Exception('Please ensure that you send through image details');
    }
    if (gettype($image_details) !== "array") {
        throw new Exception('Image details needs to be an array');
    }
    $has_id_image = false;
    $has_selfie = false;
    foreach ($image_details as $item) {
        if (gettype($item) !== "array"
            || !array_key_exists("image_type_id", $item)
            || !array_key_exists("image", $item)) {
            throw new Exception("Image details content must to be an array with 'image_type_id' and 'image' has keys");
        }
        if ($item["image_type_id"] === 1 || $item["image_type_id"] === 3) {
            $has_id_image = true;
        }
        if ($item["image_type_id"] === 0 || $item["image_type_id"] === 2) {
            $has_selfie = true;
        }
    }
    if ($job_type == JobType::DOCUMENT_VERIFICATION && !$has_id_image){
        throw new Exception('You are attempting to complete a job type 6 without providing an id card image');
    }
    if (!($has_selfie || ($job_type == JobType::DOCUMENT_VERIFICATION && $use_enrolled_image))) {
        throw new Exception('You need to send through at least one selfie image');
    }
}

/**
 * @throws Exception
 */
function validateOptions($options)
{
    foreach (array_keys($options) as $key) {
        if ($key !== "optional_callback" && gettype($options[$key]) !== "boolean") {
            throw new Exception("$key need to be a boolean");
        }
        if ($key === "optional_callback" && gettype($options[$key]) !== "string") {
            throw new Exception("$key need to be a string");
        }
    }
    if (!strlen(array_value_by_key("optional_callback", $options)) && !array_value_by_key("return_job_status", $options)) {
        throw new Exception("Please choose to either get your response via the callback or job status query");
    }
}

/**
 * 
 * @param array $expected_types Array of expected job IDs
 * @param integer $job_type User provided job ID
 * 
 * @throws Exception
 */
function validateJobTypes($expected_types, $job_type)
{
    if (!in_array($job_type, $expected_types)) {
        $expected_values = implode(", ", $expected_types);
        throw new InvalidArgumentException("job_type must be one of $expected_values");
    }
}

function array_value_by_key($key, $array)
{
    if (key_exists($key, $array)) {
        return $array[$key];
    } else {
        return null;
    }
}
