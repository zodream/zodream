<?php
namespace Zodream\Domain\Debug;

class Memory {
	public static function CPU() {
		return getrusage();             //CUP使用情况
	}

	public static function usage() {
	    return memory_get_usage();      //内存使用量
    }

    public static function peakUsage() {
        return memory_get_peak_usage(); //内存使用峰值
    }
}