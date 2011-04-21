<?php defined('SYSPATH') or die('No direct script access.');

class Hoptoad_Exception_Core extends Exception
{
    public static $previous_exception_handler;
    
    public static function handler(Exception $e)
    {
        // Send the exception to Hoptoad
        Hoptoad::instance()
            ->exception($e)
            ->notify();

        call_user_func(self::$previous_exception_handler, $e);
    }
}
