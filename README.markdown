Error logging class
===================

A simple class to log error messages, with pluggable storage for logfile
messages. Currently, there is only one logger, one that logs to a specified
text file on the filesystem, but different loggers can be added, for
example logging to a MySQL database, or perhaps to a socket.

See example.php for how to use it.

Usage:
------

	// This is all that's needed to start logging messages.
	require_once 'errorLog.php';

There are five levels of logging, each with its own function
(in order of severity):

* `log_info('This is an informational message');`
* `log_debug('This is a debug message');`
* `log_error('This is an error message');`
* `log_warn('This is a warning message');`
* `log_fatal('This is a fatal message');`

Each of these logging functions takes an optional second parameter
specifying whether to display this message in the standard output or not.
Setting the parameter to `true` will show that message. It defaults to
the config-set value, or false if none specified.

We can set a minimum level of logging, for example logging only messages
that are warnings or above. Each level of logging has a defined constant,
(in order of severity):

* `LOG_LEVEL_INFO` - Information level messages
* `LOG_LEVEL_DEBUG` - Debug level messages
* `LOG_LEVEL_WARN` - Warning level messages
* `LOG_LEVEL_ERROR` - Error level messages
* `LOG_LEVEL_FATAL` - Fatal level messages

So to only log messages of Warnings and above we can:

	log_set_log_level(LOG_LEVEL_WARN);


To initialise a logger and initial logging options, we do this through
a configuration object:


	log_configure($config);

Completed Features:
-------------------

* Get the method/function name of the callee


To-do Feature list:
-------------------

* specify log levels on a class basis
* register and trigger event-listeners by log level
* unit testing

