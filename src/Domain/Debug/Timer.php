<?php
namespace Zodream\Domain\Debug;

use Zodream\Service\Factory;
use Zodream\Infrastructure\ObjectExpand\TimeExpand;
class Timer {
	protected $startTime;

    protected $lastTime;

    protected $times = [];
	
	public function begin() {
        $this->lastTime = $this->startTime = TimeExpand::millisecond();
        $this->times['begin'] = 0;
	}

	public function record($name) {
	    $arg = TimeExpand::millisecond();
        if (array_key_exists($name, $this->times)) {
            $name .= time();
        }
        $this->times[$name] = $arg - $this->lastTime;
        $this->lastTime = $arg;
    }
	
	public function end() {
	    $this->record('end');
		return $this->getCount();
	}

	public function getCount() {
	    return $this->lastTime - $this->startTime;
    }

    /**
     * @return array
     */
	public function getTimes() {
	    return $this->times;
    }

    public function log() {
        $handle = fopen(Factory::root()->childFile('log/timer.log'), 'w');
        fwrite($handle, TimeExpand::format()."\r\n");
        fwrite($handle, $this->startTime."\r\n");
        foreach ($this->times as $key => $item) {
            fwrite($handle, $key.':'.$item."\r\n");
        }
        fwrite($handle, $this->lastTime."\r\n");
        fclose($handle);
    }
}