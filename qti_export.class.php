<?php

require_once('main.php');

/**
 * Driver to export questions to the QTI format.
 * Relies on serializers for question's formatting.
 *
 * University of Geneva
 * @author laurent.opprecht@unige.ch
 *
 */
class QtiExport{

    private $temp = ''; //temp directory used to save files
    private $question_count = 0; //number of questions written to the file so far

    /**
     * log for logging message, errors, etc.
     * @var Log
     */
    private $log = null;

    /**
     * Manifest writer
     * @var ImsCpmanifestWriter
     */
    private $manifest = null;

    /**
     * Manifest ressources writer
     * @var ImsCpmanifestWriter
     */
    private $manifest_resources = null;
    private $manifest_organization = null;

    public function __construct($log = null){
        $this->log = empty($log) ? LogEmpty::instance() : $log;
        $this->init();
    }

    public function get_question_count(){
        return $this->question_count;
    }

    public function init(){
        $this->question_count = 0;
        $this->manifest = new ImsCpmanifestWriter();
        $manifest = $this->manifest->add_manifest();
        $this->manifest_organization = $manifest->add_organizations()->add_organization();
        $this->manifest_resources = $manifest->add_resources();
        if (!$this->temp = MoodleUtil::create_temp_directory()){
            return $this->notify_lang('cannotcreatepath');
        }else{
            return true;
        }
    }

    public function add_quiz($quiz){
        $questions = $this->get_quiz_questions($quiz);
        $questions = $this->add($questions);
        $serializer = new QuizSerializer();
        $text = $serializer->serialize($quiz, $questions);
        $filepath = $this->get_quiz_filepath($quiz);
        $this->write($filepath, $text);
        $this->add_manifest_quiz($quiz);
        return $filepath;
    }

    public function add_questions($category, $include_category = false, $include_hidden = false){
        $questions = $this->get_questions($category, $include_category, $include_hidden);
        foreach($questions as $question){
            $this->add($question);
        }
    }

    public function add($question){
        if(is_array($question)){
            $result = array();
            foreach($question as $q){
                if($r = $this->add($q)){
                    foreach($r as $filepath=>$qq)
                    $result[$filepath] = $qq;
                }
            }
            return $result;
        }else{
            $this->notify_lang('export', '', $question);
            $filepath = $this->get_question_filepath($question);
            if(! $serializer = $this->create_serializer($question)){
                return $this->notify_lang('unknownquestiontype', '', $question);
            }else if(! $text = $serializer->serialize($question)){
                return $this->notify_lang('error', '', $question);
            }else if(!$this->write($filepath, $text)){
                return $this->error('cannotwriteto', '', $filepath);
            }else{
                $manifest_resource = $this->add_manifest_question($question);
                $resources = $serializer->get_resources();
                $this->export_resources($resources);
                foreach($resources as $href => $url){
                    $manifest_resource->add_file($href);
                }
                $this->question_count++;
                return array($filepath=>$question);
            }
        }
    }

    public function save($path){
        $this->manifest->save($this->temp .'/'. ImsCpmanifestWriter::MANIFEST_NAME);
        $result = MoodleUtil::archive_directory($this->temp, $path);
        fulldelete($this->temp);
        return $result;
    }

    public function get_questions($category, $include_category = false, $include_hidden = false){
        $result = array();
        $questions = get_questions_category($category);

        foreach($questions as $question){
            if((!$question->hidden || $include_category) && ($question->parent == 0 || $include_hidden)){
                $result[] = $question;
            }
        }
        return $result;
    }

    public function get_quiz_questions($quiz){
        $id = is_object($quiz) ? $quiz->id : $quiz;

        global $DB;
        global $CFG;
        $prefix = $CFG->prefix;
        $sql = 'SELECT q.* FROM {question} q, {quiz_question_instances} i
                WHERE
                    i.quiz = ' . $id .' AND
                    q.id = i.question';

        try{
            $result = $DB->get_records_sql($sql);
            if(is_array($result)){
                foreach($result as $question){
                    $question->export_process = true;//needed to export datasets
                }
            }
            get_question_options($result, true);
        }catch(Exception $e){
            debug($e);
        }
        return $result;
    }

    protected function write($path, $content){
        return FileUtil::write($path, $content);
    }

    protected function export_resources($ressources){
        $directory = $this->temp;
        foreach($ressources as $target => $url){
            $path = $directory.$target;
            FileUtil::ensure_directory($path);

            $url = explode('/', $url);
            $question_id = reset($url);
            $category_id = $url[1];
            $filename = end($url);
            $context = $this->get_context_by_category_id($category_id);

            $fs = get_file_storage();
            if($file = $fs->get_file($context->id, 'question', 'questiontext', $question_id, '/', $filename)){
                $file->copy_content_to($path);
            }
        }
    }

    function get_context_by_category_id($category) {
        global $DB;
        $contextid = $DB->get_field('question_categories', 'contextid', array('id'=>$category));
        $context = get_context_instance_by_id($contextid);
        return $context;
    }

    protected function create_serializer($question){
        return QuestionSerializer::factory($question, 'resources');
    }

    protected function get_question_filename($question){
        return $this->get_question_name($question) . '.xml';
    }

    protected function get_question_name($question){
        return 'q_'. str_pad($question->id, 8, '0', STR_PAD_LEFT);
    }

    protected function get_question_filepath($question){
        return $this->temp .'/'. $this->get_question_filename($question);
    }

    protected function get_quiz_filepath($quiz){
        return $this->temp .'/'. 'quiz_'. str_pad($quiz->id, 8, '0', STR_PAD_LEFT) . '.xml';
    }

    protected function add_manifest_question($question){

        $type = 'imsqti_item_xmlv2p0';
        $id = $this->get_question_name($question);
        $href = $this->get_question_filename($question);
        $result = $this->manifest_resources->add_resource($type, $href, $id);
        $this->add_question_metadata($result, $question);
        $result->add_file($href);

        $this->manifest_organization->add_item($id)->add_title($question->name);
        return $result;
    }

    protected function add_manifest_quiz($quiz){
        $type = 'imsqti_item_xmlv2p0';
        $path = $this->get_quiz_filepath($quiz);
        $id = basename($path);
        $href = basename($path);
        $result = $this->manifest_resources->add_resource($type, $href, $id);
        //$this->add_question_metadata($result, $question);
        $result->add_file($href);
        $this->manifest_organization->add_item($id)->add_title($quiz->name);

        return $result;
    }

    protected function add_question_metadata(ImsXmlWriter $item, $question){
        $result = $item->add_metadata('lom', '1.0');
        $lom = new LomWriter($result, 'lom');
        $general = $lom->add_general();
        $general->add_title($question->name);
        $general->add_identifier(MoodleUtil::get_catalog_name(), $question->id);
        $lifecycle = $lom->add_lifecycle();
        $lifecycle->add_status();
        return $result;
    }

    protected function notify_lang($message, $module, $a){
        $module = empty($module) ? 'quiz' : $module;
        $question_text = is_object($a) ? "$a->id - $a->name ($a->qtype)" : '';
        $a = is_object($a) ? '' : $a;
        $text = get_string($message, $module, $a);
        $text = empty($question_text) ? $text : "$text : $question_text";
        $this->log->notify($text);
        return false;
    }
}



















