<?php

use Swoole\Lock;
use Swoole\Process;
use Swoole\Timer;

if (!extension_loaded('swoole')) {
    die("No swoole module available");
}

function prepare_log_message($string) {
    $msg = sprintf("[%s] [%d] %s\n", date('Y-m-d H:i:s'), getmypid(), $string);
    return $msg;
}

$opts = getopt('l:', ['log-file:']);
$log_fname = $opts['l'] ?? $opts['log-file'] ?? false;
if ($log_fname) {
    touch($log_fname);
    $log_fd = fopen($log_fname, 'a');
    $log_lock = new Lock(SWOOLE_FILELOCK, $log_fname);

    function logger($string) {
        global $log_lock, $log_fd;
        $log_lock->lock();
        fwrite($log_fd, prepare_log_message($string));
        $log_lock->unlock();
    }
}
else {
    function logger($string) {
        echo prepare_log_message($string);
    }
}

$http_process = new swoole_process(function() {
    $http = new swoole_http_server("0.0.0.0", 80);
    $http->set([
        'document_root' => __DIR__.'/../static',
        'enable_static_handler' => true,
    ]);

    // init var
    $connection = null;

    $http->on("start", function ($server) {
        logger("HTTP Server start - master PID = {$server->master_pid}, manager PID = {$server->manager_pid}");
    });

    $http->on("shutdown", function ($server) {
        logger("HTTP Server is now shutdown");
    });

    $http->on("WorkerStart", function ($server, $worker_id) use (&$connection) {
        logger("HTTP Worker starting, ID = $worker_id, PID = {$server->worker_pid}");
        $connection = mysqli_connect('db', 'awesome-vault-monitor', 'monitor', 'awesome-vault-monitor');
        $connection->set_charset('utf8');
        logger("[$worker_id] DB Connection done");
    });

    $http->on("WorkerStop", function ($server, $worker_id) use (&$connection) {
        logger("HTTP Worker stopping, PID = $worker_id, PID = {$server->worker_pid}");
        $connection->close();
        logger("[$worker_id] DB Connection stopped");
    });

    $http->on("request", function ($request, $response) use (&$connection) {
        logger("HTTP request {$request->fd}");

        $response->header("Content-Type", "text/html");

        $q = sprintf("INSERT INTO test (pid) VALUES (%d)", getmypid());
        $connection->query($q);
        $id = $connection->insert_id;

        $response->end(<<<HTML
<!DOCTYPE html>
<html>
<head>
<link rel="stylesheet" href="/style.css"/>
<script src="https://code.jquery.com/jquery-1.12.4.min.js"></script>
</head>
<body>
<p>Hello World. ID of row: <span id="row-id">{$id}</span></p>
<ul id="messages"></ul>
<script>
$(document).ready(function() {
    var s = new WebSocket('ws://localhost:9801');
    s.onready = function() {
        s.send("Here's some text that the server is urgently awaiting!");
    };
    s.onmessage = function (event) {
        $('#messages').append('<li>'+event.data+'</li>');
    };
});
</script>
</body>
</html>
HTML
        );
    });

    $http->start();
});

$ws_process = new swoole_process(function() {
    $ws = new swoole_websocket_server("0.0.0.0", 81);

    $ws->on("start", function ($server) {
        logger("WebSocket Server start - master PID = {$server->master_pid}, manager PID = {$server->manager_pid}");
    });

    $ws->on("shutdown", function ($server) {
        logger("WebSocket Server is now shutdown");
    });

    $ws->on("WorkerStart", function ($server, $worker_id) use (&$connection) {
        logger("WebSocket Worker starting, ID = $worker_id, PID = {$server->worker_pid}");
    });

    $ws->on("WorkerStop", function ($server, $worker_id) use (&$connection) {
        logger("WebSocket Worker stopping, PID = $worker_id, PID = {$server->worker_pid}");
    });

    $ws->on('open', function(Swoole\WebSocket\Server $server, Swoole\Http\Request $request) {
        logger("Websocket connection open: {$request->fd}");
        $server->tick(1000, function($id) use ($server, $request) {
            if ($server->exist($request->fd)) {
                $server->push($request->fd, json_encode(["hello", time()]));
            }
            else {
                logger("Websocket connection seems closed: {$request->fd}. Cancelling timer");
                $server->clearTimer($id);
            }
        });
    });

    $ws->on('message', function(Swoole\WebSocket\Server $server, Swoole\WebSocket\Frame $frame) {
        logger("Websocket received message from {$frame->fd}: {$frame->data}");
        $server->push($frame->fd, json_encode(["hello", time()]));
    });

    $ws->on('close', function(Swoole\WebSocket\Server $server, int $fd) {
        logger("Websocket connection close: {$fd}");
    });

    $ws->start();
});

Process::signal(SIGCHLD, function($sig) use ($http_process, $ws_process) {
    static $http_terminated = false;
    static $ws_terminated = false;

    while($ret = Swoole\Process::wait(false)){
        if ($ret['pid'] == $http_process->pid) $http_terminated = true;
        if ($ret['pid'] == $ws_process->pid) $ws_terminated = true;
    }

    if ($http_terminated && $ws_terminated) {
        logger("All servers stopped, master process exiting");
        exit(0);
    }
});

$http_process->start();
$ws_process->start();

$exit_callback = function($sig) use ($http_process, $ws_process) {
    logger("Terminating HTTP server process");
    Process::kill($http_process->pid, SIGTERM);
    logger("Terminating WebSocket server process");
    Process::kill($ws_process->pid, SIGTERM);
};

logger("Attaching to signals");
Process::signal(SIGTERM, $exit_callback);
Process::signal(SIGINT, $exit_callback);
logger("Attached to signals");

logger("Init complete");
Timer::tick(60000, function() {
    // noop
});