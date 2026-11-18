<?php

namespace SmileIdentity;

// Replaces sleep() inside the SmileIdentity namespace so polling tests run fast
// and can check how long the poller would have waited.
function sleep(int $seconds): int
{
    $GLOBALS['smile_identity_sleeps'][] = $seconds;
    return 0;
}
