<?php

require_once 'errorLog.php';

// Configure the logging object
// -- this can be done at any stage before the application exits
//    and log entries will be cached until a logger is available.
$LOG->setLogger(
		array(
			'logger'    => 'FileErrorLog',
			'file'      => '/home/user/tmp/error-messages.txt',
			'log_level' => LOG_LEVEL_DEBUG
		)
	);

class EchoEcho {
	public function __construct() {
		$this->addListeners();	
	}

	public function addListeners() {
		global $LOG;
		$callback = array($this, 'echoLogMessages');
		log_subscribe(LOG_LEVEL_ERROR, $callback);
	}

	public function echoLogMessages($logMsg) {
		echo "Echo ", $logMsg->__toString(), "\n";
	}
	
}

$echo = new EchoEcho();


log_warn('This is a warning message', true);
log_error('This is an error message', true);



?>