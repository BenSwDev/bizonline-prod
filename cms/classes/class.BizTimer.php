<?php
class BizTimer extends Logs\Timer {
    public function __construct(){
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);

        parent::__construct(__DIR__ . '/../../logs/' . basename($trace[0]['file']) . '.log', [
            'URL'  => $_SERVER['REQUEST_URI'],
            'POST' => $_POST,
            'SESS' => $_SESSION
        ], 1);
    }
}
