<?php

require_once 'errorLog.php';

$LOG->setLogger(
		array(
			'logger' => 'FileErrorLog',
			'file'   => 'log.messages.txt'
		)
	);


$LOG->info('Initialising first message');


//print_r($LOG);

?>