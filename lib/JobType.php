<?php

class JobType
{
    // Verifies the ID information of your users using facial biometrics
    const BIOMETRIC_KYC = 1;

    // Compares a selfie to a selfie on file.
    const SMART_SELFIE_AUTHENTICATION = 2;

    // Creates an enrollee, associates a selfie with a partner_id, user_id
    const SMART_SELFIE_REGISTRATION = 4;

    // Verifies identity information of a person with their personal information and ID number from one of our supported ID Types.
    const BASIC_KYC = 5;

    // Queries Identity Information of user using ID_number.
    const ENHANCED_KYC = 5;

    // Verifies user info retrieved from the ID issuing authority.
    const DOCUMENT_VERIFICATION = 6;

    // Verifies authenticity of Document IDs, confirms it's linked to the user using facial biometrics.
    const BUSINESS_VERIFICATION = 7;

    // Updates the photo on file for an enrolled user
    const UPDATE_PHOTO = 8;

    // Compares document verification to an id check
    const COMPARE_USER_INFO = 9;

    // Verifies user selfie with info retrieved from the ID issuing authority.
    const ENHANCED_DOCUMENT_VERIFICATION = 11;
}
