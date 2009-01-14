<?php

require_once 'errorLog.php';

$LOG->setLogger(
		array(
			'logger' => 'FileErrorLog',
			'file'   => '/home/user/tmp/error-messages.txt'
		)
	);


$LOG->info('Initialising first message');


//print_r($LOG);

?>