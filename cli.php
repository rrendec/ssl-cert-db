<?php

require_once('common.php');

$now = time();
$list = get_aggregate_cert('pending', $now);
$banner = "*** Certificate(s) expiring soon ***\n\n";

foreach ($list as $crt) {
    $days = days_between($now, $crt['valid']);
    if ($days > 10) {
        break;
    }
    echo $banner;
    $banner = str_repeat('-', 72) . "\n";
    printf("Subject:   %s\n", canonical_short($crt['subject']));
    printf("Issuer:    %s\n", canonical_short($crt['issuer']));
    printf("Valid To:  %s\n", format_datetime($crt['valid']));
    printf("Days Left: %d\n", $days);
}
