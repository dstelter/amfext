<?php

require_once 'Zend/Amf/Parse/Amf3/Serializer.php';
require_once 'Zend/Amf/Parse/OutputStream.php';
require_once 'Zend/Amf/Constants.php';

class Benchmark {
	private $microtime;
	private $memory_usage;

	public function start() {
		$this->microtime = microtime(true);
		$this->memory_usage = memory_get_usage(true);	
	}

	public function stop($name) {
		$memory_usage = memory_get_usage(true) - $this->memory_usage;	
		$ms = (microtime(true) - $this->microtime) * 1000;

		echo sprintf("$name\t %0.4fms\n", $ms);
	}

	/**
	 * @source http://www.php.net/manual/de/function.filesize.php#100097
	 */
	private function formatBytes($size) {
		$units = array(' B', ' KB', ' MB', ' GB', ' TB');
		for ($i = 0; $size >= 1024 && $i < 4; $i++) $size /= 1024;
    	return round($size, 2).$units[$i];
	}
}

class AmfBenchmark extends Benchmark {
	public function encode($data, $name, $loops = 1000) {
		$name .= " {$loops}x";
		$this->start();
		for ($i = 0; $i<$loops; $i++) {
			amf_encode($data, 3);
		}
		unset($i);
		$this->stop($name . ': amfext');

		$this->start();
		for ($i = 0; $i<$loops; $i++) {
			$stream = new Zend_Amf_Parse_OutputStream();
			$serializer = new Zend_Amf_Parse_Amf3_Serializer($stream);
			$serializer->writeTypeMarker($data, null);#Zend_Amf_Constants::AMF0_AMF3
		}
		unset($serializer, $stream, $i);
		$this->stop($name . ': zend');
	}
}


$benchmark = new AmfBenchmark();

// big array
$data_array = array_fill(0, 10000, 'Tes123543543');
$benchmark->encode($data_array, 'Big array', 30);

// big object
$object = new stdClass();
$object->foo = $data_array;
$object->bar = $object;
$object->bar2 = array($data_array, str_repeat('asdjhjlksd', 1000));

$data_object = array_fill(0, 1000, 'Tes123543543');
$benchmark->encode($data_object, 'Big object', 300);
unset($data_array);

// small string
$data_string = str_repeat('asdjhjlksd', 100); // 100 chars
$benchmark->encode($data_string, 'Small string', 100000);
unset($data_string);

// long string
$data_string = str_repeat('asdjhjlksd', 10000); // 100k chars
$benchmark->encode($data_string, 'Long string', 500);
unset($data_string);

