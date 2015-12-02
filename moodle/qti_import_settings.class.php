<?php

/**
 * Settings used to import a QTI file.
 *
 * @copyright (c) 2010 University of Geneva
 * @license GNU General Public License
 * @author laurent.opprecht@unige.ch
 *
 */
class QtiImportSettings{

    const MOODLE_QUESTION_DATA = 'MOODLE_QUESTION_DATA';

    private $path = '';
    private $source_root = '';
    private $target_root = '';
    private $category = 0;

    public function __construct($path, $source_root, $target_root, $category){
        $this->path = $path;
        $this->source_root = $source_root;
        $this->target_root = $target_root;
        $this->category = $category;
    }

    public function get_path(){
        return $this->path;
    }

    public function get_directory(){
        return dirname($this->path) . '/';
    }

    public function get_source_root(){
        return $this->source_root;
    }

    public function get_target_root(){
        return $this->target_root;
    }

    public function get_category(){
        return $this->category;
    }

    private $reader = null;
    /**
     * @return ImsXmlReader
     */
    public function get_reader(){
        if(!empty($this->reader)){
            return $this->reader;
        }

        if(!file_exists($this->path)){
            return $this->reader = ImsXmlReader::get_empty_reader();
        }

        $path = $this->path;
        $ext = pathinfo($path, PATHINFO_EXTENSION);
        if($ext == 'xml'){
            return $this->reader = new ImsQtiReader($path);
        }else{
            return $this->reader = ImsXmlReader::get_empty_reader();
        }
    }

    private $_data = false;

    /**
     * Moodle's data in case of reimport
     *
     */
    public function get_data(){
        if($this->_data !== false){
            return $this->_data;
        }
        $item = $this->get_reader();
        $feedbacks = $item->list_modalFeedback();
        foreach($feedbacks as $feedback){
            if($feedback->outcomeIdentifier == self::MOODLE_QUESTION_DATA){
                $result = $feedback->text();
                $result = unserialize($result);
                return $this->_data = $result;
            }
        }
        return $this->$_data = null;
    }

}