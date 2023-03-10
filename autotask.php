<?php

require('../../../inc/includes.php');
require('../../../inc/updatebacklog.php');

$weeks = getWeeksOnly();

foreach ($weeks as $week) {
    registerBacklog($week, 1);
}
foreach ($weeks as $week) {
    registerBacklog($week, 2);
}