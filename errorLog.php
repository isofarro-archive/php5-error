<?php

define('LOG_LEVEL_INFO',   1);
define('LOG_LEVEL_DEBUG',  2);
define('LOG_LEVEL_WARN',   4);
define('LOG_LEVEL_ERROR',  8);
define('LOG_LEVEL_FATAL', 16);

interface ErrorLogStore {
	public function load();
	public function save();
	public function add($logMsg);
}


class FileErrorLog implements ErrorLogStore {
	protected $config;
	protected $log = array();
	
	public function __construct($config=false) {
		if ($config) {
			$this->setConfig($config);
		}
	}
		
	public function setConfig($config) {
		$this->config = $config;
		$this->initLogger($config);
	}


	public function load() {
		$fileName = $this->getFilename();
		if (fileName) {
		
		}	
	}
	
	public function save() {
		$fileName = $this->getFilename();
		if ($fileName) {
			$fileHandle = fopen($fileName, 'a');
			if ($fileHandle) {
				$buffer = $this->writeLogMessages();
				//echo $buffer;
				$isDone = fwrite($fileHandle, $buffer);
				if (!$isDone) {
					echo "ERROR: write log messages to $fileName failed.\n";
				}
				fclose($fileHandle);
			}  else {
				echo "ERROR: Can't get filehandle: $fileName\n";
			}
		} else {
			echo "ERROR: No log file specified.\n";
		}
	}
	
	public function add($logMsg) {
		$this->log[] = $logMsg;	
	}

	protected function initLogger($config) {
	
	}
	
	protected function getFilename() {
		if (!empty($this->config['file'])) {
			$dirname = dirname($this->config['file']);
			//echo "DEBUG: dirname: $dirname\n";
			if (is_writable($dirname)) {
				return $this->config['file'];
			} else {
				echo "ERROR: Directory is not writable!\n";
			}
		}
	}
	
	protected function writeLogMessages() {
		$buffer = array();
		//echo count($this->log), " messages to save\n";
		foreach($this->log as $msg) {
			$buffer[] = $msg->__toString();
		}
		return implode("\n", $buffer) . "\n";
	}
}


class ErrorMsg {
	protected $level;
	protected $msg;
	protected $time;
	
	function __construct($level, $msg) {
		$this->level = $level;
		$this->msg   = $msg;
		$this->time  = time();
	}
	
	public function __toString() {
		return $this->level . " [" . date('c', $this->time) . "] 	" . $this->msg;
	}
}

class ErrorLog {
	protected $log       = array();
	protected $logBuffer = array();
	protected $logLevel  = 0;

	protected $logger;
	

	public function __construct($config=false) {
		if ($config) {
			$this->setLogger($config);
		}
	}

	public function __destruct() {
		$this->save();
	}
	
	public function setLogger($config) {
		$this->initLogger($config);
		if (!empty($this->logBuffer)) {
			foreach($this->logBuffer as $logMsg) {
				$this->logger->add($logMsg);
			}
		}
	}
	
	public function setLogLevel($level) {
	
	}
	
	public function info($msg) {
		$this->log(LOG_LEVEL_INFO, $msg);
	}

	public function debug($msg) {
		$this->log(LOG_LEVEL_DEBUG, $msg);
	}

	public function warn($msg) {
		$this->log(LOG_LEVEL_WARN, $msg);
	}

	public function error($msg) {
		$this->log(LOG_LEVEL_ERROR, $msg);
	}

	public function fatal($msg) {
		$this->log(LOG_LEVEL_FATAL, $msg);
	}

	public function log($level, $msg) {
		$logMsg = new ErrorMsg($level, $msg);
		$this->log[] = $logMsg;

		if(empty($this->logger)) {
			$this->logBuffer[] = $logMsg;
		} else {
			$this->logger->add($logMsg);
		}
	}
	
	protected function initLogger($config) {
		if (!empty($config['logger'])) {
			$logClass = $config['logger'];
			$logger   = new $logClass($config);
			if (is_a($logger, 'ErrorLogStore')) {
				$this->logger = $logger;
				return true;
			} else {
				echo "ERROR: $logClass doesn't implement ErrorLogStore\n"; 
			}
		}
		return false;
	}

	protected function load() {
		echo "LOG->load()\n";
		if (!empty($this->logger)) {
			$this->log = $this->logger->load();
		}
	}

	protected function save() {
		//echo "LOG->save()\n";
		if (!empty($this->logger)) {
			$this->logger->save($this->log);
		} else {
			echo "ERROR: No logger defined\n";		
		}
	}
}

global $LOG;
if (empty($LOG)) {
	$LOG = new ErrorLog();
}

?>