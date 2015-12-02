<?php

require_once('main.php');

/**
 * Moodle entry class to import/export questions to a specific format.
 * Provides import and export to/from QTI.
 * Requires moodle 1.9 or later.
 *
 * If additional import strategies are required for specific questions' types add a class in the
 * import_strategy folder and link it to the responsibility chain in
 *
 *      QtiImportStrategyBase::create_moodle_default_strategy()
 *
 * University of Geneva
 * @author laurent.opprecht@unige.ch
 *
 */
class qformat_imsqti21 extends qformat_default {

    /**
     * @var Log
     */
    private $log = null;

    public function __construct(){
        $this->log = new LogOnline();
    }

    function provide_import(){
        return true;
    }

    function provide_export(){
        return true;
    }

    function export_file_extension() {
        return '.zip';
    }

    public function importprocess($category) {
        $importer = new QtiImport($this->log);
        return $importer->import($this->filename, $this->realfilename, $this->course, $this->category, $this->stoponerror);
    }

    function exportprocess(){

        $export = new QtiExport($this->log);
        $questions = $export->get_questions($this->category);
//        $this->notify_lang('export', '', count($questions));
        $export->add($questions);
        if($export->get_question_count()==0){
            $result = false;
            $this->notify_lang('noquestions');
        }else{
            $result = $export->save();
        }
//        $this->notify_lang('done', 'quiz_overview');
        return $result;
    }

    //END MOODLE qformat_default interface.

    public function __call($name, $args){
        $f = array($this->log, $name);
        if(is_callable($f)){
            return call_user_func_array($f, $args);
        }
    }
}
