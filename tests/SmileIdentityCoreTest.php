<?php
declare(strict_types=1);

require 'lib/SmileIdentityCore.php';

use GuzzleHttp\Client;
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

    protected int $sid_server = 0;
    protected int $partner_id = 212;

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
            "job_type" => 1,
        ];

    }

    public function testInitialize(): void
    {
        $this->assertInstanceOf('SmileIdentityCore', $this->sic);
    }

    public function testGetVersion(): void
    {
        $version = $this->sic->get_version();
        $this->assertEquals('1.1.0', $version);
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
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
                "job_type" => 1
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
     * @throws \GuzzleHttp\Exception\GuzzleException
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
        $secParam = $this->sic->generate_sec_key($this->options["signature"] );
        $timestamp = $secParam['timestamp'];
        $signature = $secParam['signature'];
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
     * @throws \GuzzleHttp\Exception\GuzzleException
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

    public function testGenerateKey(): void
    {
        $timestamp = Clock::now()->getTimestamp();
        $sec_key = $this->sic->generate_sec_key(false);
        $this->assertEquals($timestamp, $sec_key["timestamp"]);
    }
}
