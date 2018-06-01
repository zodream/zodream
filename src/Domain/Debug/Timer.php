<?php
namespace Zodream\Domain\Debug;

use Zodream\Disk\Stream;
use Zodream\Service\Factory;
use Zodream\Helpers\Time;
class Timer {
	protected $startTime;

    protected $lastTime;

    protected $times = [];
	
	public function begin() {
        $this->lastTime = $this->startTime = Time::millisecond();
        $this->times['begin'] = 0;
	}

	public function record($name) {
	    $arg = Time::millisecond();
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
        $stream = new Stream(Factory::root()->file('log/timer.log'));
        $stream->open('w')
            ->writeLine(Time::format())
            ->writeLine($this->startTime);
        foreach ($this->times as $key => $item) {
            $stream->writeLine($key.':'.$item);
        }
        $stream->writeLine($this->lastTime)
            ->close();
    }
}