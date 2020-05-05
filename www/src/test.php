<?php

use Swoole\Process;
use Swoole\Timer;

Process::signal(SIGTERM, function($sig) use (&$id) {
    echo "Terminating";
    Timer::clear($id);
});
Process::signal(SIGINT, function($sig) use (&$id) {
    echo "Terminating";
    Timer::clear($id);
});

$id = Timer::tick(1000, function () {
    echo ".\n";
});