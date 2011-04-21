<?php defined('SYSPATH') or die('No direct script access.');

// Register Hoptoad exception handler, storing the previous exception handler
// so that we can pass the exception to it as well.
Hoptoad_Exception::$previous_exception_handler = set_exception_handler(array('Hoptoad_Exception', 'handler'));
