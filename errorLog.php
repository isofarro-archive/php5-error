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
						"Writing log messages to $fileName failed.", true, 
						'FileErrorLog->save()'
					);
				}
				fclose($fileHandle);
			}  else {
				log_error(
					"Can't get filehandle: $fileName", true,
					'FileErrorLog->save()'
				);
			}
		} else {
			log_error(
				"No log file specified.", true,
				'FileErrorLog->save()'			
			);
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
				log_error(
					"Directory $dirname is not writable!", NULL,
					'FileErrorLog->getFilename()'
				);
			}
		}
	}
	
	protected function writeLogMessages() {
		$buffer = array();
		//		log_debug(count($this->log). " messages to save");
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
	protected $callee;
	
	function __construct($level, $msg, $callee=NULL) {
		$this->level = $level;
		$this->msg   = $msg;
		$this->time  = time();
		
		if (!is_null($callee)) {
			$this->callee = $callee . ' ';
		}
	}
	
	public function __toString() {
		return $this->level . 
			" [" . date('c', $this->time) . "] " .
			$this->callee .  
			$this->msg;
	}
}

class ErrorLog {
	protected $log       = array();
	protected $logBuffer = array();
	protected $logLevel  = 0;
	protected $toScreen  = false;

	protected $logger;
	
	protected $scope     = array();
	protected $callbacks = array();

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

	public function subscribe($log_level, $callback) {
		if (!empty($this->callbacks[$log_level])) {
			$this->callbacks[$log_level] = array();
		}
		$this->callbacks[$log_level][] = $callback;
	}
		
	public function setLogLevel($level, $scope=NULL) {
		if (is_int($level)) {
			if (is_null($scope)) {
				// Set the global logging level
				$this->logLevel = $level;
			} else {
				// Set the class specific log level
				if (is_string($scope)) {
					$this->scope[$scope] = $level;
				} elseif (is_object($scope)) {
					// TODO: Get the class name of the supplied reference
					$className = get_class($scope);
					$this->scope[$className] = $level;
					//echo 'Setting ', $className, ' to ', $level, "\n";
				} else {
					echo "Scope is unknown\n"; print_r($scope);				
				}
			
			}
		} else {
			$this->warn(
				"Log Level not a defined log level", NULL,
				'ErrorLog->setLogLevel()'			
			);
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
	
	public function info($msg, $toScreen=NULL) {
		$this->log(LOG_LEVEL_INFO, $msg, $toScreen);
	}

	public function debug($msg, $toScreen=NULL) {
		$this->log(LOG_LEVEL_DEBUG, $msg, $toScreen);
	}

	public function warn($msg, $toScreen=NULL) {
		$this->log(LOG_LEVEL_WARN, $msg, $toScreen);
	}

	public function error($msg, $toScreen=NULL) {
		$this->log(LOG_LEVEL_ERROR, $msg, $toScreen);
	}

	public function fatal($msg, $toScreen=NULL) {
		$this->log(LOG_LEVEL_FATAL, $msg, $toScreen);
	}

	public function log($level, $msg, $toScreen=NULL, $callee=NULL) {
		// Find the callee if none provided
		if (is_null($callee)) {
			//$callee = $this->getCallee();		
			list($callee, $class, $method) = $this->getCallee();
		} else {
			//TODO:: We have a callee passed - what does this mean??
			$class = $method = 'TBD';
		}
		
		// Check whether there is a class override
		$minLogLevel = $this->logLevel;
		if ($class) {
			if (!empty($this->scope[$class])) {
				$minLogLevel = $this->scope[$class];
				//echo "Class level logLevel: ", $minLogLevel, 
				//	" (", $this->logLevel, ")\n";
			}		
		}
			
		if ($level >= $minLogLevel) {
			// Decide whether to display this message to stdout
			if(is_null($toScreen)) {
				$toScreen = $this->toScreen;
			}

			// Translate the level integer into text
			$levelKey = $this->getLevelText($level);
			
			$logMsg = new ErrorMsg($levelKey, $msg, $callee);
			$this->log[] = $logMsg;

			// If we don't have a logger, cache the log message
			if(empty($this->logger)) {
				$this->logBuffer[] = $logMsg;
			} else {
				$this->logger->add($logMsg);
			}

			// Check whether we need to send this message to the screen
			if ($toScreen) {
				echo $logMsg->__toString(), "\n";
			}
			
			// Call any listeners to this log level
			// TODO: Decide whether the log level of the listener
			// is the actual log level or the minimum log level.
			if (!empty($this->callbacks[$level])) {
				// Call each listener
				foreach($this->callbacks[$level] as $callback) {
					//$this->debug("Callback " . $callback);
					call_user_func($callback, $logMsg);
				}
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
				$this->error(
					$logClass . " doesn't implement ErrorLogStore", true,
					'ErrorLog->initLogger()'
				); 
			}
		}
		return false;
	}

	protected function load() {
		$this->debug("LOG->load()", NULL, 'ErrorLog->load()');
		if (!empty($this->logger)) {
			$this->log = $this->logger->load();
		}
	}

	protected function save() {
		//echo "LOG->save()\n";
		if (!empty($this->logger)) {
			$this->logger->save($this->log);
		} else {
			$this->error("No logger defined", true, 'ErrorLog->save()');		
		}
	}
	
	protected function getCallee() {
		$stack = debug_backtrace();
		//		print_r($stack);
		foreach($stack as $row) {
			$callee     = '';
			$className  = NULL;
			$methodName = NULL;
			
			if (!empty($row['class'])) {
				// Skip callee if it is an ErrorLog one
				// TODO: Replace static text with a classname($this) construct
				if ($row['class']=='ErrorLog') {
					continue;
				}
				$className = $row['class'];
				$callee   .= $className . '->';
			} else {
				// Skip function if it is an ErrorLog defined one
				switch($row['function']) {
					case 'log_info':
					case 'log_debug':
					case 'log_warn':
					case 'log_error':
					case 'log_fatal':
					case 'log_configure':
					case 'log_set_log_level':
					case 'log_send_to_screen':
						continue 2;
						break;
					default:
						break;
				}
			}
			$methodName = $row['function'];
			$callee    .= $methodName . '()';
			
			// Now that we've found the callee, return it
			return array($callee, $className, $methodName);
		}
		
		// Nothing on the stack - its called from Main.
		return array('MAIN::', 'MAIN', NULL);
	}
}

global $LOG;
if (empty($LOG)) {
	$LOG = new ErrorLog();
}

function log_info($msg, $toScreen=NULL) {
	global $LOG;
	$LOG->info($msg, $toScreen);
}

function log_debug($msg, $toScreen=NULL) {
	global $LOG;
	$LOG->debug($msg, $toScreen);
}

function log_warn($msg, $toScreen=NULL) {
	global $LOG;
	$LOG->warn($msg, $toScreen);
}

function log_error($msg, $toScreen=NULL) {
	global $LOG;
	$LOG->error($msg, $toScreen);
}

function log_fatal($msg, $toScreen=false) {
	global $LOG;
	$LOG->fatal($msg, $toScreen);
}

function log_configure($config) {
	global $LOG;
	$LOG->setLogger($config);
}	

function log_set_log_level($level, $scope=NULL) {
	global $LOG;
	$LOG->setLogLevel($level, $scope);
}

function log_send_to_screen($toScreen) {
	global $LOG;
	$LOG->setToScreen($toScreen);
}

function log_subscribe($level, $callback) {
	global $LOG;
	$LOG->subscribe($level, $callback);	
}


?>