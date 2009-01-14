<?php

require_once 'errorLog.php';

$LOG->setLogger(
		array(
			'logger' => 'FileErrorLog',
			'file'   => '/home/user/tmp/error-messages.txt'
		)
	);

$LOG->setLogLevel(LOG_LEVEL_WARN);

$LOG->info('Initialising first message');
$LOG->debug('A debug message');
$LOG->warn('A warning message');
$LOG->error('An error message');
$LOG->fatal('A FATAL message');


//print_r($LOG);

?>