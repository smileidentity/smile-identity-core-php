<?php
declare(strict_types=1);

namespace sid;

require 'lib/Signature.php';
use PHPUnit\Framework\TestCase;

final class SignatureTest extends TestCase
{
    public function testGenerateSecKey(): void
    {
        $sig = new Signature;
        $sig->initialize(12345, 45678);
        $generatedKey = @$sig->generate_sec_key();
        $this->assertSame(2, count($generatedKey));
    }
}
