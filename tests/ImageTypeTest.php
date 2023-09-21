<?php
declare(strict_types=1);

use SmileIdentity\ImageType;
use PHPUnit\Framework\TestCase;

final class ImageTypeTest extends TestCase
{
    public function testImageTypeValuesMatcheNumericTags()
    {
      $this->assertEquals(ImageType::SELFIE_FILE, 0);
      $this->assertEquals(ImageType::ID_CARD_FILE, 1);
      $this->assertEquals(ImageType::SELFIE_IMAGE_STRING, 2);
      $this->assertEquals(ImageType::ID_CARD_IMAGE_STRING, 3);
      $this->assertEquals(ImageType::LIVENESS_IMAGE_FILE, 4);
      $this->assertEquals(ImageType::ID_CARD_BACK_FILE, 5);
      $this->assertEquals(ImageType::LIVENESS_IMAGE_STRING, 6);
      $this->assertEquals(ImageType::ID_CARD_BACK_STRING, 7);
    }
}
