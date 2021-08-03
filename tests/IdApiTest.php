<?php
declare(strict_types=1);

require 'lib/IdApi.php';

use GuzzleHttp\Exception\GuzzleException;
use PHPUnit\Framework\TestCase;
use Ouzo\Utilities\Clock;

final class IdApiTest extends TestCase
{
    /**
     * @var string[]
     */
    private array $id_info;
    private array $partner_params;
    private sid\IdApi $idApi;

    protected function setUp(): void
    {
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

        Clock::freeze('2011-01-02 12:34');
        $sid_server = 1;
        $partner_id = 212;
        $default_callback = 'https://google.com';
        $api_key = file_get_contents(__DIR__ . "/assets/ApiKey.pub");
        $this->idApi = new sid\IdApi($partner_id, $default_callback, $api_key, $sid_server);
    }

    /**
     * @throws GuzzleException
     */
    public function testAsyncSubmitJob()
    {
        $this->idApi->submit_job($this->partner_params, $this->id_info, true);
    }

    /**
     * @throws GuzzleException
     */
    public function testSyncSubmitJob()
    {
        $this->idApi->submit_job($this->partner_params, $this->id_info, false);
    }
}
