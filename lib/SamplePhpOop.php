<?php

namespace model;
require 'config.php';

class SampleClass
{
    public function getName()
    {
        echo DEFAULT_JOB_STATUS_TIMEOUT;
    }
}

$sampleClass = new SampleClass;
$sampleClass->getName();
