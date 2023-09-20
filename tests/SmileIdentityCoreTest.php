<?php
declare(strict_types=1);

require 'lib/SmileIdentityCore.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Ouzo\Utilities\Clock;
use PHPUnit\Framework\TestCase;

final class SmileIdentityCoreTest extends TestCase
{
    protected SmileIdentityCore $sic;
    protected array $idParams;
    protected array $imageDetails;
    protected array $options;
    protected array $partnerParams;

    protected string $sid_server = "0";
    protected string $partner_id = "212";

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        Clock::freeze('2011-01-02 12:34');
        $default_callback = 'https://google.com';
        $api_key = file_get_contents(__DIR__ . "/assets/ApiKey.pub");
        $this->sic = new SmileIdentityCore($this->partner_id, $default_callback, $api_key, $this->sid_server);
        $this->idParams = [
            "first_name" => "FirstName",
            "middle_name" => "LastName",
            "last_name" => "MiddleName",
            "country" => "NG",
            "id_type" => "PASSPORT",
            "id_number" => "A00000000",
            "dob" => "1989-09-20",
            "phone_number" => "",
            "entered" => True,
        ];
        $this->imageDetails = [["image_type_id" => 2, "image" => "base6image"]];
        $this->options = [
            "return_job_status" => True,
            "return_history" => True,
            "return_images" => True,
            "signature" => True,
        ];
        $this->partnerParams = [
            "user_id" => "user-id",
            "job_id" => "job-id",
            "job_type" => JobType::BIOMETRIC_KYC,
        ];
    }

    public function testInitialize(): void
    {
        $this->assertInstanceOf('SmileIdentityCore', $this->sic);
    }

    public function testGetVersion(): void
    {
        $version = $this->sic->get_version();
        $this->assertEquals(Config::VERSION, $version);
    }

    /**
     * @throws GuzzleException
     */

    public function testSubmitJobUploadsZipFileWhenReturnJobStatusIsFalse(): void
    {
        $req = [
            "signature" => " wnCJH84LcCCWnpfYD7tLMFtg5m0JFLV7I5ZFzPdtnpo=",
            "timestamp" => "2021-09-22T14:13:09+0000",
            "callback_url" => "https://webhook.site/f5108a87-78f1-4180-ae43-f8235968d1e0",
            "file_name" => "selfie.zip",
            "model_parameters" => "",
            "partner_params" => [
                "job_id" => "php-job-id-04",
                "user_id" => "php-user-18",
                "job_type" => JobType::BIOMETRIC_KYC
            ],

            "smile_client_id" => 212,
        ];
        $resp_body = [
            "upload_url" => "https://upload-url.com",
            "ref_id" => "212-0000058873-dwhsn9nsax3onnbf8mc1rifirl44z",
            "smile_job_id" => "0000058873",
            "camera_config" => null,
            "code" => 2202
        ];

        $mock = new MockHandler([
            new Response(200, [], json_encode($resp_body)),
            new Response(200, [])
        ]);

        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);
        $this->sic->setClient($client);

        $this->options["return_job_status"] = false;
        $result = $this->sic->submit_job($this->partnerParams, $this->imageDetails, $this->idParams, $this->options);
        $this->assertEquals(["smile_job_id" => "0000058873", "success" => true], $result);
    }

    /**
     * @throws GuzzleException
     */
    public function testSubmitJobUploadsZipFileWhenReturnJobStatusIsTrue(): void
    {
        $resp_body = [
            "upload_url" => "https://upload-url.com",
            "ref_id" => "212-0000058873-dwhsn9nsax3onnbf8mc1rifirl44z",
            "smile_job_id" => "0000058873",
            "camera_config" => null,
            "code" => 2202
        ];
        $signatureKey = $this->sic->generate_signature();
        $timestamp = $signatureKey['timestamp'];
        $signature = $signatureKey['signature'];
        $getStatusResult = '{"timestamp": "' . $timestamp . '", "signature": "' . $signature . '", "job_complete": true, "job_success": false, "code": "2302", "result": {}, "history": [], "image_links": {"selfie_image": "https://selfie-image.com"}}';

        $mock = new MockHandler([
            new Response(200, [], json_encode($resp_body)),
            new Response(200, []),
            new Response(200, [], $getStatusResult)
        ]);

        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);
        $this->sic->setClient($client);

        $this->options["return_job_status"] = true;
        $result = $this->sic->submit_job($this->partnerParams, $this->imageDetails, $this->idParams, $this->options);

        $expectedResult = array_merge(json_decode($getStatusResult, true), ["success" => true]);
        $this->assertEquals($expectedResult, $result);
    }

    public function shouldCallIDApiWhenSubmitJobIsCalledWithJobType5(): void
    {
    }

    /**
     * @throws GuzzleException
     */
    public function testSubmitJobShouldRaiseErrorWhenPreUploadFails()
    {
        $this->expectException(Exception::class);
        $resp_body = '{"error": "Job already exists. Did you mean to set the retry flag to true?", "code":2215}';
        $mock = new MockHandler([
            new Response(400, [], $resp_body),
        ]);

        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);
        $this->sic->setClient($client);

        $this->sic->submit_job($this->partnerParams, $this->imageDetails, $this->idParams, $this->options);
    }

    /**
     * @throws GuzzleException
     */
    public function testSubmitJobShouldRequireIdCardImageForJT6(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("You are attempting to complete a job type 6 without providing an id card image");
        $resp_body = [
            "upload_url" => "https://upload-url.com",
            "ref_id" => "212-0000058873-dwhsn9nsax3onnbf8mc1rifirl44z",
            "smile_job_id" => "0000058873",
            "camera_config" => null,
            "code" => 2202
        ];
        $partnerParams = [
            "user_id" => "user-id",
            "job_id" => "job-id",
            "job_type" => JobType::DOCUMENT_VERIFICATION,
        ];
        $idParams = [
            "country" => "NG",
            "id_type" => "PASSPORT",
            "id_number" => "A00000000",
        ];
        $imageDetails = [["image_type_id" => ImageType::SELFIE_FILE, "image" => "base6image"]];
        $signatureKey = $this->sic->generate_signature();
        $timestamp = $signatureKey['timestamp'];
        $signature = $signatureKey['signature'];
        $getStatusResult = '{"timestamp": "' . $timestamp . '", "signature": "' . $signature . '", "job_complete": true, "job_success": false, "code": "2302", "result": {}, "history": [], "image_links": {"selfie_image": "https://selfie-image.com"}}';

        $mock = new MockHandler([
            new Response(200, [], json_encode($resp_body)),
            new Response(200, []),
            new Response(200, [], $getStatusResult)
        ]);

        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);
        $this->sic->setClient($client);

        $this->sic->submit_job($partnerParams, $imageDetails, $idParams, $this->options);
    }

    /**
     * @throws GuzzleException
     */
    public function testSubmitJobShouldRequireIdCardImageForJT11(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("You are attempting to complete a job type 11 without providing an id card image");
        $resp_body = [
            "upload_url" => "https://upload-url.com",
            "ref_id" => "212-0000058873-dwhsn9nsax3onnbf8mc1rifirl44z",
            "smile_job_id" => "0000058873",
            "camera_config" => null,
            "code" => 2202
        ];
        $partnerParams = [
            "user_id" => "user-id",
            "job_id" => "job-id",
            "job_type" => JobType::ENHANCED_DOCUMENT_VERIFICATION,
        ];
        $idParams = [
            "country" => "NG",
            "id_type" => "PASSPORT",
            "id_number" => "A00000000",
        ];
        $imageDetails = [["image_type_id" => ImageType::SELFIE_FILE, "image" => "base6image"]];
        $signatureKey = $this->sic->generate_signature();
        $timestamp = $signatureKey['timestamp'];
        $signature = $signatureKey['signature'];
        $getStatusResult = '{"timestamp": "' . $timestamp . '", "signature": "' . $signature . '", "job_complete": true, "job_success": false, "code": "2302", "result": {}, "history": [], "image_links": {"selfie_image": "https://selfie-image.com"}}';

        $mock = new MockHandler([
            new Response(200, [], json_encode($resp_body)),
            new Response(200, []),
            new Response(200, [], $getStatusResult)
        ]);

        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);
        $this->sic->setClient($client);

        $this->sic->submit_job($partnerParams, $imageDetails, $idParams, $this->options);
    }

    /**
     * @throws GuzzleException
     */
    public function testSubmitJobShouldRequireCountryInIdInfoForJT6(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Please make sure that country is included in the id_info and has a value");
        $resp_body = [
            "upload_url" => "https://upload-url.com",
            "ref_id" => "212-0000058873-dwhsn9nsax3onnbf8mc1rifirl44z",
            "smile_job_id" => "0000058873",
            "camera_config" => null,
            "code" => 2202
        ];
        $partnerParams = [
            "user_id" => "user-id",
            "job_id" => "job-id",
            "job_type" => JobType::DOCUMENT_VERIFICATION,
        ];
        $idParams = [
            "id_type" => "PASSPORT",
            "id_number" => "A00000000",
        ];
        $imageDetails = [["image_type_id" => ImageType::SELFIE_FILE, "image" => "base6image"]];
        $signatureKey = $this->sic->generate_signature();
        $timestamp = $signatureKey['timestamp'];
        $signature = $signatureKey['signature'];
        $getStatusResult = '{"timestamp": "' . $timestamp . '", "signature": "' . $signature . '", "job_complete": true, "job_success": false, "code": "2302", "result": {}, "history": [], "image_links": {"selfie_image": "https://selfie-image.com"}}';

        $mock = new MockHandler([
            new Response(200, [], json_encode($resp_body)),
            new Response(200, []),
            new Response(200, [], $getStatusResult)
        ]);

        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);
        $this->sic->setClient($client);

        $this->sic->submit_job($partnerParams, $imageDetails, $idParams, $this->options);
    }

    /**
     * @throws GuzzleException
     */
    public function testSubmitJobShouldRequireCountryInIdInfoForJT11(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Please make sure that country is included in the id_info and has a value");
        $resp_body = [
            "upload_url" => "https://upload-url.com",
            "ref_id" => "212-0000058873-dwhsn9nsax3onnbf8mc1rifirl44z",
            "smile_job_id" => "0000058873",
            "camera_config" => null,
            "code" => 2202
        ];
        $partnerParams = [
            "user_id" => "user-id",
            "job_id" => "job-id",
            "job_type" => JobType::ENHANCED_DOCUMENT_VERIFICATION,
        ];
        $idParams = [
            "id_type" => "PASSPORT",
            "id_number" => "A00000000",
        ];
        $imageDetails = [["image_type_id" => ImageType::SELFIE_FILE, "image" => "base6image"]];
        $signatureKey = $this->sic->generate_signature();
        $timestamp = $signatureKey['timestamp'];
        $signature = $signatureKey['signature'];
        $getStatusResult = '{"timestamp": "' . $timestamp . '", "signature": "' . $signature . '", "job_complete": true, "job_success": false, "code": "2302", "result": {}, "history": [], "image_links": {"selfie_image": "https://selfie-image.com"}}';

        $mock = new MockHandler([
            new Response(200, [], json_encode($resp_body)),
            new Response(200, []),
            new Response(200, [], $getStatusResult)
        ]);

        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);
        $this->sic->setClient($client);

        $this->sic->submit_job($partnerParams, $imageDetails, $idParams, $this->options);
    }

    /**
     * @throws GuzzleException
     */
    public function testSubmitJobShouldRequireIdTypeInIdInfoForJT11(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Please make sure that id_type is included in the id_info and has a value");
        $resp_body = [
            "upload_url" => "https://upload-url.com",
            "ref_id" => "212-0000058873-dwhsn9nsax3onnbf8mc1rifirl44z",
            "smile_job_id" => "0000058873",
            "camera_config" => null,
            "code" => 2202
        ];
        $partnerParams = [
            "user_id" => "user-id",
            "job_id" => "job-id",
            "job_type" => JobType::ENHANCED_DOCUMENT_VERIFICATION,
        ];
        $idParams = [
            "country" => "NG",
            "id_number" => "A00000000",
        ];
        $imageDetails = [["image_type_id" => ImageType::SELFIE_FILE, "image" => "base6image"]];
        $signatureKey = $this->sic->generate_signature();
        $timestamp = $signatureKey['timestamp'];
        $signature = $signatureKey['signature'];
        $getStatusResult = '{"timestamp": "' . $timestamp . '", "signature": "' . $signature . '", "job_complete": true, "job_success": false, "code": "2302", "result": {}, "history": [], "image_links": {"selfie_image": "https://selfie-image.com"}}';

        $mock = new MockHandler([
            new Response(200, [], json_encode($resp_body)),
            new Response(200, []),
            new Response(200, [], $getStatusResult)
        ]);

        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);
        $this->sic->setClient($client);

        $this->sic->submit_job($partnerParams, $imageDetails, $idParams, $this->options);
    }

    /**
     * @throws GuzzleException
     */
    public function testSubmitJobSuccessForJT6(): void
    {
        $resp_body = [
            "upload_url" => "https://upload-url.com",
            "ref_id" => "212-0000058873-dwhsn9nsax3onnbf8mc1rifirl44z",
            "smile_job_id" => "0000058873",
            "camera_config" => null,
            "code" => 2202
        ];
        $partnerParams = [
            "user_id" => "user-id",
            "job_id" => "job-id",
            "job_type" => JobType::DOCUMENT_VERIFICATION,
        ];
        $idParams = [
            "country" => "NG",
            "id_number" => "A00000000",
        ];
        $imageDetails = [["image_type_id" => ImageType::SELFIE_FILE, "image" => "base6image"], ["image_type_id" => ImageType::ID_CARD_FILE, "image" => "base6image"]];
        $signatureKey = $this->sic->generate_signature();
        $timestamp = $signatureKey['timestamp'];
        $signature = $signatureKey['signature'];
        $getStatusResult = '{"timestamp": "' . $timestamp . '", "signature": "' . $signature . '", "job_complete": true, "job_success": false, "code": "2302", "result": {}, "history": [], "image_links": {"selfie_image": "https://selfie-image.com"}}';

        $mock = new MockHandler([
            new Response(200, [], json_encode($resp_body)),
            new Response(200, []),
            new Response(200, [], $getStatusResult)
        ]);

        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);
        $this->sic->setClient($client);

        $result = $this->sic->submit_job($partnerParams, $imageDetails, $idParams, $this->options);
        
        $this->assertTrue($result["success"]);
    }

    /**
     * @throws GuzzleException
     */
    public function testSubmitJobSuccessForJT11(): void
    {
        $resp_body = [
            "upload_url" => "https://upload-url.com",
            "ref_id" => "212-0000058873-dwhsn9nsax3onnbf8mc1rifirl44z",
            "smile_job_id" => "0000058873",
            "camera_config" => null,
            "code" => 2202
        ];
        $partnerParams = [
            "user_id" => "user-id",
            "job_id" => "job-id",
            "job_type" => JobType::ENHANCED_DOCUMENT_VERIFICATION,
        ];
        $idParams = [
            "country" => "NG",
            "id_type" => "PASSPORT"
        ];
        $imageDetails = [["image_type_id" => ImageType::SELFIE_FILE, "image" => "base6image"], ["image_type_id" => ImageType::ID_CARD_FILE, "image" => "base6image"]];
        $signatureKey = $this->sic->generate_signature();
        $timestamp = $signatureKey['timestamp'];
        $signature = $signatureKey['signature'];
        $getStatusResult = '{"timestamp": "' . $timestamp . '", "signature": "' . $signature . '", "job_complete": true, "job_success": false, "code": "2302", "result": {}, "history": [], "image_links": {"selfie_image": "https://selfie-image.com"}}';

        $mock = new MockHandler([
            new Response(200, [], json_encode($resp_body)),
            new Response(200, []),
            new Response(200, [], $getStatusResult)
        ]);

        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);
        $this->sic->setClient($client);

        $result = $this->sic->submit_job($partnerParams, $imageDetails, $idParams, $this->options);
        
        $this->assertTrue($result["success"]);
    }

    /**
     * @throws GuzzleException
     */
    public function testSubmitJobShouldRequiresAtLeastOneSelfieForJTOtherThanJT6(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("You need to send through at least one selfie image");
        $resp_body = [
            "upload_url" => "https://upload-url.com",
            "ref_id" => "212-0000058873-dwhsn9nsax3onnbf8mc1rifirl44z",
            "smile_job_id" => "0000058873",
            "camera_config" => null,
            "code" => 2202
        ];
        $partnerParams = [
            "user_id" => "user-id",
            "job_id" => "job-id",
            "job_type" => JobType::BIOMETRIC_KYC,
        ];
        $idParams = [
            "country" => "NG",
            "id_number" => "A00000000",
        ];
        $imageDetails = [["image_type_id" => ImageType::ID_CARD_FILE, "image" => "base6image"]];
        $sigParam = $this->sic->generate_signature();
        $timestamp = $sigParam['timestamp'];
        $signature = $sigParam['signature'];
        $getStatusResult = '{"timestamp": "' . $timestamp . '", "signature": "' . $signature . '", "job_complete": true, "job_success": false, "code": "2302", "result": {}, "history": [], "image_links": {"selfie_image": "https://selfie-image.com"}}';

        $mock = new MockHandler([
            new Response(200, [], json_encode($resp_body)),
            new Response(200, []),
            new Response(200, [], $getStatusResult)
        ]);

        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);
        $this->sic->setClient($client);

        $this->sic->submit_job($partnerParams, $imageDetails, $idParams, $this->options);
    }

    public function testGenerateSignature(): void
    {
        $timestamp = Clock::now()->format(DateTimeInterface::ATOM);
        $signature = $this->sic->generate_signature($timestamp);
        $this->assertEquals($timestamp, $signature["timestamp"]);
    }
    
    public function testGetWebToken(): void
    {
        $expectedResult = [
            "success" => "true",
            "token" => "<WEB_TOKEN>"
        ];
        
        $mock = new MockHandler([
            new Response(200, [], json_encode($expectedResult))
        ]);
        
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);
        $this->sic->setClient($client);

        $timestamp = Clock::now()->getTimestamp();
        $user_id = "<USER_ID>";
        $job_id = "<JOB_ID>";
        $product = "<PRODUCT_TYPE>";
        $result = $this->sic->get_web_token($user_id, $job_id, $product, $timestamp);
        $this->assertEquals($result, $expectedResult);
    }
    
    public function testSmileServices(): void
    {
        $expectedResult = [
            "success" => "true",
            "token" => "<WEB_TOKEN>"
        ];
        
        $mock = new MockHandler([
            new Response(200, [], json_encode($expectedResult))
        ]);
        
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);
        $this->sic->setClient($client);
        
        $result = $this->sic->query_smile_id_services();
        $this->assertEquals($result, $expectedResult);
    }

    public function testKybSuccessForBusinessRegistrationType()
    {
        $default_callback = 'https://google.com';
        $api_key = file_get_contents(__DIR__ . "/assets/ApiKey.pub");
        $sid_core = new SmileIdentityCore($this->partner_id, $default_callback, $api_key, $this->sid_server);

        $partner_params = array(
            'user_id' => '1',
            'job_id' => '1',
            'job_type' => JobType::BUSINESS_VERIFICATION
        );

        $id_info = array(
            'country' => 'NG',
            'id_type' => 'BUSINESS_REGISTRATION',
            'id_number' => '00000000000',
        );

        $client = $this->getMockClient();
        $sid_core->setClient($client);

        $job = $sid_core->submit_job($partner_params, [], $id_info, []);
        $this->assertEquals(array("success" => true), $job);
    }

    public function testKybSuccessForBasicBusinessRegistrationType()
    {
        $default_callback = 'https://google.com';
        $api_key = file_get_contents(__DIR__ . "/assets/ApiKey.pub");
        $sid_core = new SmileIdentityCore($this->partner_id, $default_callback, $api_key, $this->sid_server);

        $partner_params = array(
            'user_id' => '1',
            'job_id' => '1',
            'job_type' => JobType::BUSINESS_VERIFICATION
        );

        $id_info = array(
            'country' => 'NG',
            'id_type' => 'BASIC_BUSINESS_REGISTRATION',
            'id_number' => '00000000000',
        );

        $client = $this->getMockClient();
        $sid_core->setClient($client);

        $job = $sid_core->submit_job($partner_params, [], $id_info, []);
        $this->assertEquals(array("success" => true), $job);
    }

    public function testKybSuccessForTaxInformationType()
    {
        $default_callback = 'https://google.com';
        $api_key = file_get_contents(__DIR__ . "/assets/ApiKey.pub");
        $sid_core = new SmileIdentityCore($this->partner_id, $default_callback, $api_key, $this->sid_server);

        $partner_params = array(
            'user_id' => '1',
            'job_id' => '1',
            'job_type' => JobType::BUSINESS_VERIFICATION
        );

        $id_info = array(
            'country' => 'NG',
            'id_type' => 'TAX_INFORMATION',
            'id_number' => '00000000000',
        );

        $client = $this->getMockClient();
        $sid_core->setClient($client);

        $job = $sid_core->submit_job($partner_params, [], $id_info, []);
        $this->assertEquals(array("success" => true), $job);
    }

    public function testExceptionWhenJobTypeIsInvalid()
    {
        $expected_values = implode(", ", array(
            JobType::BIOMETRIC_KYC,
            JobType::BUSINESS_VERIFICATION,
            JobType::DOCUMENT_VERIFICATION,
            JobType::ENHANCED_DOCUMENT_VERIFICATION,
            JobType::SMART_SELFIE_AUTHENTICATION,
        ));
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("job_type must be one of $expected_values");
        
        $api_key = file_get_contents(__DIR__ . "/assets/ApiKey.pub");
        $default_callback = 'https://google.com';
        $sid_core = new SmileIdentityCore($this->partner_id, $default_callback, $api_key, $this->sid_server);

        $partner_params = array(
            'user_id' => '1',
            'job_id' => '1',
            'job_type' => 50 // invalid
        );

        $id_info = array(
            'country' => 'NG',
            'id_type' => 'BUSINESS_REGISTRATION',
            'id_number' => '00000000000',
        );

        $sid_core->submit_job($partner_params, [], $id_info, []);
    }

    public function testInvalidIdTypeExceptionForKyb()
    {
        $expected_types = implode(", ", BusinessVerificationType::ALL);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("id_type must be one of $expected_types");
        
        $api_key = file_get_contents(__DIR__ . "/assets/ApiKey.pub");
        $default_callback = 'https://google.com';
        $sid_core = new SmileIdentityCore($this->partner_id, $default_callback, $api_key, $this->sid_server);

        $partner_params = array(
            'user_id' => '1',
            'job_id' => '1',
            'job_type' => JobType::BUSINESS_VERIFICATION
        );

        $id_info = array(
            'country' => 'NG',
            'id_type' => 'INVALID_TYPE',
            'id_number' => '00000000000',
        );

        $sid_core->submit_job($partner_params, [], $id_info, []);
    }

    private function getMockClient()
    {
        $mock = new MockHandler([
            new Response(200, [], '{"success":true}'),
        ]);

        $handler = HandlerStack::create($mock);
        return new Client(['handler' => $handler]);
    }
}