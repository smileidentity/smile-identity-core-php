<?php
declare(strict_types=1);

require 'lib/IdApi.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Ouzo\Utilities\Clock;
use PHPUnit\Framework\TestCase;

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
            'job_type' => 5
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

        $signature = new Signature($this->api_key, $this->partner_id);
        $this->data = array(
            'language' => 'php',
            'callback_url' => $this->default_callback,
            'partner_params' => $this->partner_params,
            'sec_key' => $signature->generate_sec_key()["sec_key"],
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

        $mock = new MockHandler([
            new Response(200, [], '{"success":true}'),
        ]);

        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);
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

        $mock = new MockHandler([
            new Response(200, [], '{"success":true}'),
        ]);

        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);
        $this->idApi->setClient($client);
        $job = $this->idApi->submit_job($this->partner_params, $this->id_info, ['use_async' => false]);
        $this->assertEquals(array("success" => true), $job);
    }
}
