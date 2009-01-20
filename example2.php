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


log_warn('This is a warning message', true);


class ErrorLogUser {

	function logErrors() {
		log_error("This is an error message sent from a class", true);
	}
	
	function logInfo() {
		log_info("This is an informational message logged from a class", true);
	}

}

$loguser = new ErrorLogUser();

// Set a class specific log level
log_set_log_level(LOG_LEVEL_INFO, $loguser);

$loguser->logErrors();
$loguser->logInfo();

?>