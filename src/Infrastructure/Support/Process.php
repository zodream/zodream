<?php
namespace Zodream\Infrastructure\Support;

use Exception;

class Process {
    const STDIN = 0;
    const STDOUT = 1;
    const STDERR = 2;

    const NOT_STARTED = 0;
    const RUNNING = 1;
    const FINISHED = 2;

    private $command;

    private $useSTDIN = false;
    private $useSTDOUT = true;
    private $useSTDERR = true;

    private $storeSTDOUT = true;
    private $storeSTDERR = true;

    private $timeout = null;
    private $timedOut = false;
    private $killed = false;

    private $descriptors;
    private $startTime = null;
    private $endTime = null;
    private $process; // proc_open resource
    private $pipes; // file pointers to stdin, stdout, stderr
    private $sleeptime = 100; // ms to sleep while waiting for processes
    private $cwd = null;
    private $env = null;

    private $stdout = ""; // container for process output (stdout)
    private $stderr = ""; // container for process output (stderr)

    private $state = self::NOT_STARTED;
    private $pid = null;
    private $running = null;
    private $exitCode = null;

    /**
     * @param null $command
     * @param array $options
     * @return Process
     */
    public static function factory($command = null, $options = []) {
        $obj = new self();

        if ($command !== null) {
            $obj->setCommand($command);
        }

        if (isset($options['cwd'])) {
            $obj->setCwd($options['cwd']);
        }

        if (isset($options['env'])) {
            $obj->setEnv($options['env']);
        }

        if (isset($options['useSTDIN'])) {
            $obj->useSTDIN($options['useSTDIN']);
        }

        if (isset($options['useSTDOUT'])) {
            $obj->useSTDOUT($options['useSTDOUT']);
        }

        if (isset($options['useSTDERR'])) {
            $obj->useSTDERR($options['useSTDERR']);
        }

        if (isset($options['storeSTDOUT'])) {
            $obj->useSTDERR($options['storeSTDOUT']);
        }

        if (isset($options['storeSTDERR'])) {
            $obj->useSTDERR($options['storeSTDERR']);
        }

        if (isset($options['timeout'])) {
            $obj->setTimeout($options['timeout']);
        }

        if (isset($options['sleeptime'])) {
            $obj->setSleepTime($options['sleeptime']);
        }

        return $obj;
    }

    protected function updateDescriptors() {
        $descriptors = [];

        if ($this->useSTDIN) {
            $descriptors[self::STDIN] = ['pipe', 'r'];
        }

        if ($this->useSTDOUT) {
            $descriptors[self::STDOUT] = ['pipe', 'w'];
        }

        if ($this->useSTDERR) {
            $descriptors[self::STDERR] = ['pipe', 'w'];
        }

        $this->descriptors = $descriptors;
    }

    public function start() {
        if ($this->state !== self::NOT_STARTED) {
            throw new Exception(
                __('Process was already started.')
            );
        }

        $this->updateDescriptors();

        $this->process = @proc_open($this->command, $this->descriptors, $this->pipes, $this->cwd, $this->env);

        if (!($this->process) || !is_resource($this->process)) {
            throw new Exception(
                __('Unable to execute command: {command}', [
                    'command' => $this->command
                ])
            );
        }

        // Set the pipes as non-blocking
        foreach ([self::STDOUT, self::STDERR] as $pipe) {
            if (isset($this->descriptors[$pipe]) && $this->descriptors[$pipe]) {
                stream_set_blocking($this->pipes[$pipe], false);
            }
        }

        $this->state = self::RUNNING;
        $this->startTime = microtime(true);
        $this->updateStatus();

        return $this;
    }

    private function updateStatus() {
        $processStatus = proc_get_status($this->process);

        $this->setPid($processStatus['pid']);
        $this->setRunning($processStatus['running']);
        $this->setExitCode($processStatus['exitcode']);
    }

    public function running() {
        $this->updateStatus();
        return $this->running;
    }

    public function update() {
        $this->updateStatus();

        $stdout = "";
        $stderr = "";
        $readStreams = [];

        if (isset($this->pipes[self::STDOUT])) {
            $readStreams[] = $this->pipes[self::STDOUT];
        }

        if (isset($this->pipes[self::STDERR])) {
            $readStreams[] = $this->pipes[self::STDERR];
        }

        if (count($readStreams) == 0) {
            // Nothing to do
            return compact('stdout', 'stderr');
        }

        $write = null;
        $expect = null;
        $changed = stream_select($readStreams, $write, $expect, 0, 200000);

        if (false === $changed) {
            throw new Exception('stream error');
        }

        if (0 === $changed) {
            return compact('stdout', 'stderr');
        }

        if (isset($this->descriptors[self::STDOUT]) && $this->descriptors[self::STDOUT]) {
            $stdout = stream_get_contents($this->pipes[self::STDOUT]);
            if ($this->storeSTDOUT && mb_strlen($stdout)) {
                $this->stdout .= $stdout;
            }
        }

        if (isset($this->descriptors[self::STDERR]) && $this->descriptors[self::STDERR]) {
            $stderr = stream_get_contents($this->pipes[self::STDERR]);
            if ($this->storeSTDERR && mb_strlen($stderr)) {
                $this->stderr .= $stderr;
            }
        }

        return compact('stdout', 'stderr');
    }

    public function join() {
        while ($this->running()) {
            extract($this->update());

            if ($this->timeout && !$this->killed && (microtime(true) - $this->startTime) > $this->timeout) {
                $this->timedOut = true;
                $this->kill();
            }

            if (!mb_strlen($stdout) && !mb_strlen($stderr)) {
                // No output, wait some time
                usleep($this->sleeptime * 1000);
            }
        }

        return $this;
    }

    public function stop() {
        $this->endSend();
        foreach ([self::STDOUT, self::STDERR] as $pipe) {
            if (isset($this->pipes[$pipe]) && $this->pipes[$pipe]) {
                fclose($this->pipes[$pipe]);
                unset($this->pipes[$pipe]);
            }
        }
        $exitcode = proc_close($this->process);
        $this->setExitCode(trim($exitcode));
        return $this->exitCode;
    }

    public function kill($signal = 15) {
        $this->updateStatus();
        if ($this->running()) {
            proc_terminate($this->process, $signal);
            $this->killed = true;
        }

        return $this;
    }

    public function send($data, $end = false) {
        if (isset($this->pipes[self::STDIN])) {
            $bytes = fwrite($this->pipes[self::STDIN], $data);

            if ($end) {
                $this->endSend();
            }

            return $this;
        }
        throw new Exception(
            __('STDIN is not available')
        );
    }

    public function endSend() {
        if (isset($this->pipes[self::STDIN])) {
            fclose($this->pipes[self::STDIN]);
            unset($this->pipes[self::STDIN]);
        }

        return $this;
    }

    public function getOutput() {
        return [
            'stdout' => $this->stdout,
            'stderr' => $this->stderr,
        ];
    }

    public function getCommand() {
        return $this->command;
    }

    public function setCommand($command) {
        $this->command = $command;

        return $this;
    }

    public function useSTDIN($useSTDIN = true) {
        $this->useSTDIN = $useSTDIN;

        return $this;
    }

    public function useSTDOUT($useSTDOUT = true) {
        $this->useSTDOUT = $useSTDOUT;

        return $this;
    }

    public function useSTDERR($useSTDERR = true) {
        $this->useSTDERR = $useSTDERR;

        return $this;
    }

    public function storeSTDOUT($storeSTDOUT = true) {
        $this->storeSTDOUT = $storeSTDOUT;

        return $this;
    }

    public function storeSTDERR($storeSTDERR = true) {
        $this->storeSTDERR = $storeSTDERR;

        return $this;
    }

    public function getTimeout() {
        return $this->timeout;
    }

    public function setTimeout($timeout) {
        $this->timeout = $timeout;

        return $this;
    }

    public function timedOut() {
        return $this->timedOut;
    }

    protected function setTimedOut($timedOut) {
        $this->timedOut = $timedOut;

        return $this;
    }

    public function getKilled() {
        return $this->killed;
    }

    protected function setKilled($killed) {
        $this->killed = $killed;

        return $this;
    }

    protected function setDescriptors($descriptors) {
        $this->descriptors = $descriptors;

        return $this;
    }

    public function getStartTime() {
        return $this->startTime;
    }

    public function getEndTime() {
        return $this->endTime;
    }

    public function getRunTime() {
        if ($this->startTime && $this->endTime) {
            return ($this->endTime - $this->startTime);
        }

        return false;
    }

    public function getStdout() {
        return $this->stdout;
    }

    public function getStderr() {
        return $this->stderr;
    }

    public function getPid() {
        return $this->pid;
    }

    protected function setPid($pid) {
        $this->pid = $pid;

        return $this;
    }

    public function isRunning() {
        return $this->running;
    }

    protected function setRunning($running) {
        $this->running = $running;

        if (!$running && $this->state == self::RUNNING) {
            $this->state = self::FINISHED;
            $this->endTime = microtime(true);
        }

        return $this;
    }

    public function getExitCode() {
        return $this->exitCode;
    }

    protected function setExitCode($exitCode) {
        if ($exitCode > -1) {
            // don't set it if there was an error
            $this->exitCode = $exitCode;
        }

        return $this;
    }

    public function getSleeptime() {
        return $this->sleeptime;
    }

    public function setSleepTime($sleeptime) {
        $this->sleeptime = $sleeptime;

        return $this;
    }

    public function getCwd() {
        return $this->cwd;
    }

    public function setCwd($cwd) {
        $this->cwd = $cwd;

        return $this;
    }

    public function getEnv() {
        return $this->env;
    }

    public function setEnv($env) {
        $this->env = $env;

        return $this;
    }

    public function addEnv($key, $val) {
        $this->env[$key] = $val;

        return $this;
    }
}