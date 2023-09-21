<?php

namespace SmileIdentity;

class ImageType
{
    // Selfie image in a file format
    const SELFIE_FILE = 0;

    // ID card image in a file format
    const ID_CARD_FILE = 1;

    // Selfie image as a base64 image string
    const SELFIE_IMAGE_STRING = 2;

    // ID card as a base64 image string
    const ID_CARD_IMAGE_STRING = 3;

    // Liveness image in a file format
    const LIVENESS_IMAGE_FILE = 4;

    // ID card image(back) in a file format
    const ID_CARD_BACK_FILE = 5;

    // Liveness image as a base64 image string
    const LIVENESS_IMAGE_STRING = 6;

    // ID card image(back)as a base64 image string
    const ID_CARD_BACK_STRING = 7;
}
