<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use SmileIdentity\BusinessVerificationType;

final class BusinessVerificationTypeTest extends TestCase
{
    public function testBusinessVerificationTypeValuesMatcheNumericTags()
    {
      $this->assertEquals(BusinessVerificationType::BASIC_BUSINESS_REGISTRATION, 'BASIC_BUSINESS_REGISTRATION');
      $this->assertEquals(BusinessVerificationType::BUSINESS_REGISTRATION, 'BUSINESS_REGISTRATION');
      $this->assertEquals(BusinessVerificationType::TAX_INFORMATION, 'TAX_INFORMATION');
    }
}
