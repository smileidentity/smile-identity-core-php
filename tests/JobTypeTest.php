<?php
declare(strict_types=1);

use SmileIdentity\JobType;
use PHPUnit\Framework\TestCase;

final class JobTypeTest extends TestCase
{
    public function testJobTypeValuesMatcheNumericTags()
    {
      $this->assertEquals(JobType::BIOMETRIC_KYC, 1);
      $this->assertEquals(JobType::SMART_SELFIE_AUTHENTICATION, 2);
      $this->assertEquals(JobType::SMART_SELFIE_REGISTRATION, 4);
      $this->assertEquals(JobType::BASIC_KYC, 5);
      $this->assertEquals(JobType::ENHANCED_KYC, 5);
      $this->assertEquals(JobType::DOCUMENT_VERIFICATION, 6);
      $this->assertEquals(JobType::BUSINESS_VERIFICATION, 7);
    }
}
