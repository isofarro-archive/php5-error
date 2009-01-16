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

?>