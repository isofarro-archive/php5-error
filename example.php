<?php

require_once 'errorLog.php';

$LOG->info('Initialising first message');

$LOG->setLogger(
		(object)array(
			'logger' => 'FileErrorLog',
			'file'   => 'errorLog.output.txt'
		)
	);

print_r($LOG);

?>