<?php

require_once 'errorLog.php';

// Configure the logging object
// -- this can be done at any stage before the application exits
//    and log entries will be cached until a logger is available.
$LOG->setLogger(
		array(
			'logger'    => 'FileErrorLog',
			'file'      => '/home/user/tmp/error-messages.txt',
			'append'    => 'false',
			'to_screen' => 'true',
			'log_level' => LOG_LEVEL_ERROR
		)
	);

// Override the logging level permanently
$LOG->setLogLevel(LOG_LEVEL_WARN);
// or
log_set_log_level(LOG_LEVEL_WARN);

// Override whether to send log messages to the screen
$LOG->setToScreen(true);
// or
log_send_to_screen(true);

// Using the global logging file
$LOG->info('Initialising first message');
$LOG->debug('A debug message');
$LOG->warn('A warning message');
$LOG->error('An error message');
$LOG->fatal('A FATAL message');

// using the shortcut functions
log_info("A second info message");
log_debug("A second debug message");
log_warn("A second warn message");
log_error("A second error message");
log_fatal("A second FATAL message");


//print_r($LOG);

?>