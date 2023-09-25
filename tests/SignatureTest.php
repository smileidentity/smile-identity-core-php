<?php
declare(strict_types=1);

use Ouzo\Utilities\Clock;
use SmileIdentity\Signature;
use PHPUnit\Framework\TestCase;

final class SignatureTest extends TestCase
{
    private Signature $sig;

    protected function setUp(): void
    {
        Clock::freeze('2011-01-02 12:34');
        $partner_id = 212;
        $api_key = file_get_contents(__DIR__ . "/assets/ApiKey.pub");
        $this->sig = new Signature($partner_id, $api_key);
    }

    public function testGenerateSignature()
    {
        $timestamp = Clock::now()->format(DateTimeInterface::ATOM);
        $signature = $this->sig->generate_signature($timestamp);
        $this->assertSame(2, count($signature));
        $this->assertSame($timestamp, $signature['timestamp']);
    }

    public function testConfirmSignature()
    {
        $timestamp = Clock::now()->getTimestamp();
        $signature = $this->sig->generate_signature($timestamp)['signature'];
        $confirm_signature = $this->sig->confirm_signature($timestamp, $signature);
        $this->assertTrue($confirm_signature);

        $timestamp = Clock::now()->format(DateTimeInterface::ATOM);
        $signature = $this->sig->generate_signature($timestamp)['signature'];
        $confirm_signature = $this->sig->confirm_signature($timestamp, $signature);
        $this->assertTrue($confirm_signature);
    }
}