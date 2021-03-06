<?php

if(!defined('E_RECOVERABLE_ERROR')) {
    define('E_RECOVERABLE_ERROR', 4096);
}
if(!defined('E_DEPRECATED')) {
    define('E_DEPRECATED', 8192);
}
if(!defined('E_USER_DEPRECATED ')) {
    define('E_USER_DEPRECATED ', 16384);
}

class Insight_Plugin_Error extends Insight_Plugin_API {

    protected $traceOffset = 6;

    protected $errorConsole = null;
    protected $errorTypes = null;
    protected $exceptionConsole = null;
    protected $inExceptionHandler = false;

    protected $errorHistory = array();

    protected static $ERROR_CONSTANTS = array('E_ERROR' => E_ERROR,
                                              'E_WARNING' => E_WARNING,
                                              'E_PARSE' => E_PARSE,
                                              'E_NOTICE' => E_NOTICE,
                                              'E_CORE_ERROR' => E_CORE_ERROR,
                                              'E_CORE_WARNING' => E_CORE_WARNING,
                                              'E_COMPILE_ERROR' => E_COMPILE_ERROR,
                                              'E_COMPILE_WARNING' => E_COMPILE_WARNING,
                                              'E_USER_ERROR' => E_USER_ERROR,
                                              'E_USER_WARNING' => E_USER_WARNING,
                                              'E_USER_NOTICE' => E_USER_NOTICE,
                                              'E_STRICT' => E_STRICT,
                                              'E_RECOVERABLE_ERROR' => E_RECOVERABLE_ERROR,
                                              'E_DEPRECATED' => E_DEPRECATED,
                                              'E_USER_DEPRECATED' => E_USER_DEPRECATED,
                                              'E_ALL' => E_ALL);

    protected $_errorReportingInfo = null;
    protected $_conditionalErrorConsole = null;

    /**
     * Capture all errors and send to provided console
     * 
     * @return mixed Returns a string containing the previously defined error handler (if any)
     */
    public function onError($console, $types = E_ALL) {

        $this->errorConsole = $console;
        $this->errorTypes = $types;

        $this->_conditionalErrorConsole = $this->errorConsole->on('FirePHP: Show all PHP Errors (except:)');

        if ($this->_errorReportingInfo === null) {
            $this->_errorReportingInfo = self::parseErrorReportingBitmask(error_reporting());

            foreach (self::$ERROR_CONSTANTS as $constant => $bit) {
                switch($constant) {
                    case 'E_ERROR':
                    case 'E_PARSE':
                    case 'E_CORE_ERROR':
                    case 'E_CORE_WARNING':
                    case 'E_COMPILE_ERROR':
                    case 'E_COMPILE_WARNING':
                    case 'E_STRICT':
                    case 'E_ALL':
                        // ignore for now
                        break;
                    default:
                        $this->_conditionalErrorConsole->on($constant);
                        break;
                }
            }
        }

        //NOTE: The following errors will not be caught by this error handler:
        //      E_ERROR, E_PARSE, E_CORE_ERROR,
        //      E_CORE_WARNING, E_COMPILE_ERROR,
        //      E_COMPILE_WARNING, E_STRICT
        return set_error_handler(array($this,'_errorHandler'));     
    }

    public function _errorHandler($errno, $errstr, $errfile, $errline, $errcontext) {
        if(!$this->errorConsole) {
            return;
        }

        // if error has been suppressed with @
        if (error_reporting() == 0) {
            return;
        }

        if($this->errorHistory[$errno . ':' . $errstr . ':' . $errfile . ':' . $errline]) {
            // a repeated error
            return;
        }

        // we ignore the error if we should based on error_reporting() and the client is not forcing all errors to show
        if (in_array($errno, $this->_errorReportingInfo['absentBits']) && !$this->_conditionalErrorConsole->is(true)) {
            return;
        }

        // if the client is forcing all error to show see if this error is excluded
        if ($this->_conditionalErrorConsole->is(true) && $this->_conditionalErrorConsole->on($this->_errorReportingInfo['bitToStr'][$errno])->is(true)) {
            return;
        }

        // log error if applicable
        if(ini_get('log_errors') && ini_get('error_log')) {
            $file = ini_get('error_log');
            if(file_exists($file)) {
                if($handle = fopen($file, 'a')) {
                    $line = array();
                    $line[] = '[' . date('Y-m-d H:i:s') . ']';
                    $line[] = 'PHP ' . $this->errorLabelForNumber($errno) . ':';
                    $line[] = $errstr;
                    $line[] = 'in';
                    $line[] = $errfile;
                    $line[] = 'on line';
                    $line[] = $errline;
                    fwrite($handle, implode(' ', $line) . "\n");
                    fclose($handle);
                }
            }
        }

        $this->errorHistory[$errno . ':' . $errstr . ':' . $errfile . ':' . $errline] = true;

        // ignore assertion errors
        if(substr($errstr, 0, 8)=='assert()' && preg_match_all('/^assert\(\) \[<a href=\'function.assert\'>function.assert<\/a>\]: Assertion (.*) failed$/si', $errstr, $m)) {
            return;
        }

        // Only log errors we are asking for
        if ($this->errorTypes & $errno) {
            $this->errorConsole->setTemporaryTraceOffset($this->traceOffset);

            $meta = array(
                'encoder.rootDepth' => 5,
                'encoder.exception.traceOffset' => 1
            );

            // TODO: Custom renderers for specific errors
            if(substr($errstr, 0, 16) == 'Undefined index:' ||
               substr($errstr, 0, 17) == 'Undefined offset:' ||
               substr($errstr, 0, 19) == 'Undefined variable:' ||
               substr($errstr, 0, 25) == 'Use of undefined constant' ||
               $errstr == 'Trying to get property of non-object' ||
               $errstr == 'Only variable references should be returned by reference'
            ) {
                $meta['encoder.exception.traceMaxLength'] = 1;
            } else
            if(substr($errstr, 0, 8) == 'Function' && substr($errstr, -13, 13) == 'is deprecated') {
                $meta['encoder.exception.traceMaxLength'] = 2;
            }

            $this->errorConsole->meta($meta)->error(new ErrorException($this->_errorReportingInfo['bitToStr'][$errno] . ' - ' . $errstr, 0, $errno, $errfile, $errline));
        }
    }
   

    /**
     * Capture exceptions and send to provided console
     * 
     * @return mixed Returns the name of the previously defined exception handler,
     *               or NULL on error.
     *               If no previous handler was defined, NULL is also returned.
     */   
    public function onException($console) {
        $this->exceptionConsole = $console;
        return set_exception_handler(array($this,'_exceptionHandler'));     
    }

    function _exceptionHandler($exception) {
        if(!$this->exceptionConsole) {
            return;
        }

        // TODO: Test this
        if($this->inExceptionHandler===true) {
            trigger_error('Error sending exception');
        }
        
        $this->inExceptionHandler = true;

        $this->logException($exception);

        // NOTE: This produces some junk in the output. Looks like a bug in PHP?
        header('HTTP/1.1 500 Internal Server Error');
        header('Status: 500');

        try {
            $this->exceptionConsole->setTemporaryTraceOffset(-1);
            $this->exceptionConsole->meta(array(
                'encoder.rootDepth' => 5,
                'encoder.exception.traceOffset' => -1
            ))->error($exception);
        } catch(Exception $e) {
            trigger_error('Error sending exception: ' + $e);
        }
        $this->inExceptionHandler = false;
    }

    public function handleException($exception, $console=null) {
        if(!$console) {
            $console = $this->exceptionConsole;
        }
        if(!$console) {
            trigger_error('No exception console set for engine. See onException().');
            return;
        }

        $this->logException($exception);

        $console->setTemporaryTraceOffset(-1);
        $console->meta(array(
            'encoder.rootDepth' => 5,
            'encoder.exception.traceOffset' => -1
        ))->error($exception);
    }
    
    public function logException($exception) {
        // log exception if applicable
        if(ini_get('log_errors') && ini_get('error_log')) {
            $file = ini_get('error_log');
            if(file_exists($file)) {
                if($handle = fopen($file, 'a')) {
                    $line = array();
                    $line[] = '[' . date('Y-m-d H:i:s') . ']';
                    $line[] = 'PHP Exception(' . get_class($exception) . '):';
                    $line[] = $exception->getMessage();
                    $line[] = 'in';
                    $line[] = $exception->getFile();
                    $line[] = 'on line';
                    $line[] = $exception->getLine();
                    fwrite($handle, implode(' ', $line) . "\n");
                    fclose($handle);
                }
            }
        }
    }

    /**
     * @see http://www.php.net/manual/en/errorfunc.constants.php
     */
    public static function parseErrorReportingBitmask($bitmask) {
        $info = array(
            'bitmask' => $bitmask,
            'present' => array(),
            'absent' => array()
        );
        foreach( self::$ERROR_CONSTANTS as $constant => $bit ) {
            if( ($bitmask & constant($constant)) > 0 ) {
                $info['present'][] = $constant;
                $info['presentBits'][] = $bit;
            } else {
                $info['absent'][] = $constant;
                $info['absentBits'][] = $bit;
            }
            $info['bitToStr'][$bit] = $constant;
        }
        return $info;
    }

    public function errorLabelForNumber($number) {
        switch($number){
            case E_ERROR:               return "Error";
            case E_WARNING:             return "Warning";
            case E_PARSE:               return "Parse Error";
            case E_NOTICE:              return "Notice";
            case E_CORE_ERROR:          return "Core Error";
            case E_CORE_WARNING:        return "Core Warning";
            case E_COMPILE_ERROR:       return "Compile Error";
            case E_COMPILE_WARNING:     return "Compile Warning";
            case E_USER_ERROR:          return "User Error";
            case E_USER_WARNING:        return "User Warning";
            case E_USER_NOTICE:         return "User Notice";
            case E_STRICT:              return "Strict Notice";
            case E_RECOVERABLE_ERROR:   return "Recoverable Error";
            case E_DEPRECATED:          return "Deprecated";
            case E_USER_DEPRECATED:     return "User Deprecated";
            default:                    return "Unknown error ($number)";
        }
    }    
}
