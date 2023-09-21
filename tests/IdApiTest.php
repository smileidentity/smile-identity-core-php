<?php
declare(strict_types=1);

// require 'lib/IdApi.php';

use GuzzleHttp\Client;
use SmileIdentity\IdApi;
use Ouzo\Utilities\Clock;
use SmileIdentity\JobType;
use GuzzleHttp\HandlerStack;
use SmileIdentity\Signature;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Exception\GuzzleException;

final class IdApiTest extends TestCase
{
    private $api_key;
    private array $id_info;
    private array $partner_params;
    private IdApi $idApi;
    private string $default_callback;
    private int $partner_id;
    private array $data;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        Clock::freeze('2011-01-02 12:34');
        $sid_server = 0;
        $this->partner_id = 212;
        $this->default_callback = 'https://google.com';
        $this->api_key = file_get_contents(__DIR__ . "/assets/ApiKey.pub");

        $this->partner_params = array(
            'user_id' => '1',
            'job_id' => '1',
            'job_type' => JobType::ENHANCED_KYC
        );

        $this->id_info = array(
            'first_name' => 'John',
            'last_name' => 'Doe',
            'middle_name' => '',
            'country' => 'NG',
            'id_type' => 'BVN',
            'id_number' => '00000000000',
            'phone_number' => '0726789065'
        );

        $signature = new Signature($this->partner_id, $this->api_key);
        $this->data = array(
            'language' => 'php',
            'callback_url' => $this->default_callback,
            'partner_params' => $this->partner_params,
            'signature' => $signature->generate_signature()["signature"],
            'timestamp' => Clock::now()->getTimestamp(),
            'partner_id' => $this->partner_id
        );
        $this->idApi = new IdApi($this->partner_id, $this->default_callback, $this->api_key, $sid_server);
    }

    /**
     * @throws GuzzleException
     */
    public function testAsyncSubmitJob()
    {
        $data = array_merge($this->data, $this->id_info);
        $json_data = json_encode($data, JSON_PRETTY_PRINT);

        $client = $this->getMockClient();
        $this->idApi->setClient($client);
        $job = $this->idApi->submit_job($this->partner_params, $this->id_info, ['use_async' => true]);
        $this->assertEquals(array("success" => true), $job);
    }

    /**
     * @throws GuzzleException
     */
    public function testSyncSubmitJob()
    {
        $data = array_merge($this->data, $this->id_info);
        $json_data = json_encode($data, JSON_PRETTY_PRINT);

        $client = $this->getMockClient();
        $this->idApi->setClient($client);
        $job = $this->idApi->submit_job($this->partner_params, $this->id_info, ['use_async' => false]);
        $this->assertEquals(array("success" => true), $job);
    }

    public function testExceptionWhenJobTypeIsInvalid()
    {
        $expected_values = implode(", ", array(JobType::ENHANCED_KYC));
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("job_type must be $expected_values");
        
        $api_key = file_get_contents(__DIR__ . "/assets/ApiKey.pub");
        $id_api = new IdApi(001, "https://callback.smileidentity.com", $api_key, 0);

        $partner_params = array(
            'user_id' => '1',
            'job_id' => '1',
            'job_type' => 2 // invalid
        );

        $id_info = array(
            'country' => 'NG',
            'id_type' => 'PASSPORT',
            'id_number' => '00000000000',
        );

        $id_api->submit_job($partner_params, $id_info, []);
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
