<?php

define('LOG_LEVEL_INFO',   1);
define('LOG_LEVEL_DEBUG',  2);
define('LOG_LEVEL_WARN',   4);
define('LOG_LEVEL_ERROR',  8);
define('LOG_LEVEL_FATAL', 16);


interface ErrorLogStore {
	public function load();
	public function save();
	public function clear();
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
			$mode = $this->getLogMode();
			$fileHandle = fopen($fileName, $mode);
			if ($fileHandle) {
				$buffer = $this->writeLogMessages();
				//echo $buffer;
				$isDone = fwrite($fileHandle, $buffer);
				if (!$isDone) {
					log_error(
						"Writing log messages to $fileName failed.", true
					);
				}
				fclose($fileHandle);
			}  else {
				log_error("Can't get filehandle: $fileName", true);
			}
		} else {
			log_error("No log file specified.", true);
		}
	}
	
	public function clear() {
		// Clear down the array
		$this->log = array();
		
		// Clear down the file
		file_put_contents($this->config['file'], '');
	}
	
	public function add($logMsg) {
		$this->log[] = $logMsg;	
	}

	protected function initLogger($config) {
	
	}

	protected function getLogMode() {
		$mode ='a';
		if (!empty($this->config['append'])) {
			if ($this->config['append']=='false') {
				$mode = 'w';
			}
		}
		return $mode;	
	}
	
	protected function getFilename() {
		if (!empty($this->config['file'])) {
			$dirname = dirname($this->config['file']);
			//log_debug("dirname: $dirname");
			if (is_writable($dirname)) {
				return $this->config['file'];
			} else {
				log_error("Directory $dirname is not writable!");
			}
		}
	}
	
	protected function writeLogMessages() {
		$buffer = array();
		//log_debug(count($this->log). " messages to save");
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
	protected $toScreen  = false;

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
		if (is_int($level)) {
			$this->logLevel = $level;
		} else {
			$this->warn("Log Level not a defined log level");
		}
	}
	
	public function setToScreen($toScreen) {
		if (is_bool($toScreen)) {
			$this->toScreen = $toScreen;
		} elseif ($toScreen=='true') {
			$this->toScreen = true;
		} elseif ($toScreen=='false') {
			$this->toScreen = false;
		}
	}
	
	public function clearLogs() {
		if (!empty($this->logger)) {
			$this->logger->clear();
		}
	}
	
	public function info($msg, $toScreen=false) {
		$this->log(LOG_LEVEL_INFO, $msg, $toScreen);
	}

	public function debug($msg, $toScreen=false) {
		$this->log(LOG_LEVEL_DEBUG, $msg, $toScreen);
	}

	public function warn($msg, $toScreen=false) {
		$this->log(LOG_LEVEL_WARN, $msg, $toScreen);
	}

	public function error($msg, $toScreen=false) {
		$this->log(LOG_LEVEL_ERROR, $msg, $toScreen);
	}

	public function fatal($msg, $toScreen=false) {
		$this->log(LOG_LEVEL_FATAL, $msg, $toScreen);
	}

	public function log($level, $msg, $toScreen=false) {
		if ($level >= $this->logLevel) {
			$levelKey = $this->getLevelText($level);
			$logMsg = new ErrorMsg($levelKey, $msg);
			$this->log[] = $logMsg;

			if(empty($this->logger)) {
				$this->logBuffer[] = $logMsg;
			} else {
				$this->logger->add($logMsg);
			}

			// Check whether we need to send this message to the screen
			if ($this->toScreen || $toScreen) {
				echo $logMsg->__toString(), "\n";
			}
		}
	}
	
	protected function getLevelText($level) {
		switch($level) {
			case LOG_LEVEL_INFO:
				return 'INFO';
				break;
			case LOG_LEVEL_DEBUG:
				return 'DEBUG';
				break;
			case LOG_LEVEL_WARN:
				return 'WARN';
				break;
			case LOG_LEVEL_ERROR:
				return 'ERROR';
				break;
			case LOG_LEVEL_FATAL:
				return 'FATAL';
				break;
			default:
				return '----';
				break;
		}
	}
	
	protected function initLogger($config) {
		// Check for any logging level options
		if (!empty($config['log_level'])) {
			$this->setLogLevel($config['log_level']);
		}	
	
		if (!empty($config['to_screen'])) {
			$this->setToScreen($config['to_screen']);
		}	

		if (!empty($config['logger'])) {
			$logClass = $config['logger'];
			$logger   = new $logClass($config);
			if (is_a($logger, 'ErrorLogStore')) {
				$this->logger = $logger;
				return true;
			} else {
				$this->error($logClass . " doesn't implement ErrorLogStore", true); 
			}
		}
		return false;
	}

	protected function load() {
		$this->debug("LOG->load()");
		if (!empty($this->logger)) {
			$this->log = $this->logger->load();
		}
	}

	protected function save() {
		//echo "LOG->save()\n";
		if (!empty($this->logger)) {
			$this->logger->save($this->log);
		} else {
			$this->error("No logger defined", true);		
		}
	}
}

global $LOG;
if (empty($LOG)) {
	$LOG = new ErrorLog();
}

function log_info($msg, $toScreen=false) {
	global $LOG;
	$LOG->info($msg);
}

function log_debug($msg, $toScreen=false) {
	global $LOG;
	$LOG->debug($msg);
}

function log_warn($msg, $toScreen=false) {
	global $LOG;
	$LOG->warn($msg);
}

function log_error($msg, $toScreen=false) {
	global $LOG;
	$LOG->error($msg);
}

function log_fatal($msg, $toScreen=false) {
	global $LOG;
	$LOG->fatal($msg);
}

function log_configure($config) {
	global $LOG;
	$LOG->setLogger($config);
}

function log_set_log_level($level) {
	global $LOG;
	$LOG->setLogLevel($level);
}

function log_send_to_screen($toScreen) {
	global $LOG;
	$LOG->setToScreen($toScreen);
}


?>