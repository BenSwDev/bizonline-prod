<?php
namespace Logs;

class Timer {
    private $log;
    private $start;
    private $limit;

    public function __construct($file, $initA = [], $limit = 0)
    {
        if (!file_exists($file))
            touch($file);

        $this->filepath = realpath($file);
        $this->limit    = intval($limit);

        $this->log = ['','','---------------------', date('Y-m-d H:i:s')];

        if ($initA){
            if (is_array($initA))
                foreach($initA as $key => $val)
                    $this->log[] = $key . ': ' . (is_scalar($val) ? $val : print_r($val, true));
            else
                $this->log[] = is_scalar($initA) ? $initA : print_r($initA, true);
        }

        $this->start = microtime(true);
    }

    public function __destruct(){
        $time  = microtime(true) - $this->start;
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);

        $this->log[] = str_pad('+' . $time, 20, ' ', STR_PAD_RIGHT) . ($trace[0]['file'] ? basename($trace[0]['file']) . ':' . $trace[0]['line'] : '') . ' - timer terminated.';

        if ($this->filepath && $time > $this->limit)
            file_put_contents($this->filepath, implode(PHP_EOL, $this->log), FILE_APPEND);
    }

    public function log($remark = ''){
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);

        $this->log[] = str_pad('+' . (microtime(true) - $this->start), 20, ' ', STR_PAD_RIGHT) . basename($trace[0]['file']) . ':' . $trace[0]['line'] . ($remark ? ' - ' . $remark : '');
    }
}
