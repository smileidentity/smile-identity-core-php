<?php

class BusinessVerificationType
{
    // Search basic business registration information
    const BASIC_BUSINESS_REGISTRATION = "BASIC_BUSINESS_REGISTRATION";

    // Search full business registration information
    const BUSINESS_REGISTRATION = "BUSINESS_REGISTRATION";

    // Search Tax information
    const TAX_INFORMATION = "TAX_INFORMATION";

    const ALL = array(self::BASIC_BUSINESS_REGISTRATION, self::BUSINESS_REGISTRATION, self::TAX_INFORMATION);
}
