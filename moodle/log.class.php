<?php

/**
 * Base class for logs.
 *
 * University of Geneva
 * @author laurent.opprecht@unige.ch
 *
 */
class LogBase{

    public function write($message){
        if(!empty($message)){
            $this->execute_write($message);
        }
    }

    public function notify($message){
        global $OUTPUT;
        if(is_array($message)){
            foreach($message as $m){
                $this->write($OUTPUT->notification($message));
            }
        }else{
            $this->write($OUTPUT->notification($message));
        }
    }

    public function notify_lang($message, $module = '', $a =null){
        if(empty($module)){
            $module = 'qformat_imsqti21';
        }
        if(is_null($a)){
            $text = get_string($message, $module);
        }else{
            $text = get_string($message, $module, $a);
            $text = str_replace('$a', $a, $text);
        }
        $this->notify($text);
    }

    protected function execute_write($message){
        return false;
    }

}

/**
 * Stores messages in a internal array for later retrieval.
 *
 * University of Geneva
 * @author laurent.opprecht@unige.ch
 *
 */
class LogOffline extends LogBase{

    private $messages = array();

    protected function execute_write($message){
        $this->messages[] = $message;
    }

}

/**
 * Display messages on the standard output.
 *
 * University of Geneva
 * @author laurent.opprecht@unige.ch
 *
 */
class LogOnline extends LogBase{

    protected function execute_write($message){
        echo $message;
    }
}

/**
 * Empty object pattern. Does nothing.
 *
 * University of Geneva
 * @author laurent.opprecht@unige.ch
 *
 */
class LogEmpty extends LogBase{

    private static $instance = null;

    public static function instance(){
        self::$instance = empty(self::$instance) ? new LogEmpty() : self::$instance;
        return self::$instance;
    }

    public function write($message){
        return false;
    }

    public function notify($message){
        return false;
    }

    public function notify_lang($message, $module = '', $a =''){
        return false;
    }
}
