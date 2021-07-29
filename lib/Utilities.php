<?php
namespace sid;
use Exception;

class Utilities {
    /**
     * @throws Exception
     */
    public static function dateTimeToTimeStamp($dateTime)
    {
        $d = date_create_from_format('d-m-Y H:i:s', $dateTime);
        if ($d === false) {
            throw new Exception("Incorrect date string");
        } else {
            return $d->getTimestamp();
        }
    }

    /**
     * @throws Exception
     */
    public static function timeStampToDateTime($timestamp) {
        if(self::isTimestamp($timestamp)) return date('m/d/Y H:i:s', $timestamp);
        throw new Exception("Incorrect timestamp");
    }

    /**
     * @param $timestamp
     * @return bool
     */
    public static function isTimestamp($timestamp): bool
    {
        if(ctype_digit($timestamp) && strtotime(date('Y-m-d H:i:s', $timestamp)) === (int)$timestamp) {
            return true;
        } else {
            return false;
        }
    }
}
