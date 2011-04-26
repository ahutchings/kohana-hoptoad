<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Hoptoad exception class
 *
 * @package    Hoptoad
 * @category   Exceptions
 * @author     Andrew Hutchings
 * @copyright  (c) 2011 Andrew Hutchings
 */
class Hoptoad_Exception_Core extends Exception
{
    /**
	 * @var  callback  previously defined exception handler
	 */
    public static $previous_exception_handler;

	/**
	 * Exception handler, sends the exception to Hoptoad and passes the
	 * exception to the previously defined exception handler.
	 *
	 * @param   object   exception object
	 * @return  void
	 */
    public static function handler(Exception $e)
    {
        // Send the exception to Hoptoad
        Hoptoad::instance()
            ->exception($e)
            ->notify();

        // Pass the exception to the previously defined exception handler
        call_user_func(self::$previous_exception_handler, $e);
    }
}
