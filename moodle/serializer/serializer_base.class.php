<?php

/**
 *
 *
 * @copyright (c) 2010 University of Geneva
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author laurent.opprecht@unige.ch
 *
 */
class SerializerBase{

    const FORMAT_MOODLE_AUTO_FORMAT = 0;
    const FORMAT_HTML = 1;
    const FORMAT_PLAIN_TEXT = 2;
    const FORMAT_WIKI_LIKE = 3;
    const FORMAT_MARKDOWN = 4;

    const GENERAL_FEEDBACK = 'GENERAL_FEEDBACK';
    const PENALTY = 'PENALTY';

    /**
     * Returns the tool name used to generate qti files.
     * Mostly used to identify if a file is a reimport.
     *
     */
    public static function get_tool_name(){
        return Qti::get_tool_name('moodle');
    }


    private $resource_manager;

    public function __construct($item='resources'){
        if(is_string($item)){
            $this->resource_manager = new QtiExportResourceManager($item);
        }else{
            $this->resource_manager = $item;
        }
    }

    public function get_resource_manager(){
        return $this->resource_manager;
    }

    public function get_resources(){
        $result = $this->resource_manager->get_resources();
        return $result;
    }

    //TRANSLATE TEXT

    protected function translate_feedback_text($text, $text_format=self::FORMAT_HTML, $question=null){
        if(empty($text)){
            return $text;
        }else if($text_format == self::FORMAT_PLAIN_TEXT){
            return "<span>$text</span>";
        }else if($text_format == self::FORMAT_MOODLE_AUTO_FORMAT){
            return $text;
        }else if($text_format ==  self::FORMAT_HTML){
            $doc = new DOMDocument();
            $doc->loadHTML('<?xml encoding="UTF-8">' . $text);
            $this->translate_nodes($doc->childNodes, $question);
            $body = $doc->getElementsByTagName('body')->item(0);

            $result = $doc->saveXML($body);
            $result = str_replace('<body>', '', $result);
            $result = str_replace('</body>', '', $result);
            return $result;
        }else{
            return '';
        }
    }

    protected function translate_question_text($text, $text_format=self::FORMAT_HTML, $question=null){
        if(empty($text)){
            return $text;
        }else if($text_format == self::FORMAT_PLAIN_TEXT){
            return "<span>$text</span>";
        }else if($text_format == self::FORMAT_MOODLE_AUTO_FORMAT){
            return $text;
        }else if($text_format ==  self::FORMAT_HTML){
            $doc = new DOMDocument();
            $doc->loadHTML('<?xml encoding="UTF-8">' . $text);
            $this->translate_nodes($doc->childNodes, $question);
            $body = $doc->getElementsByTagName('body')->item(0);

            $result = $doc->saveXML($body);
            $result = str_replace('<body>', '', $result);
            $result = str_replace('</body>', '', $result);
            return $result;
        }else{
            return '';
        }
    }

    private function translate_node($node, $question){
        $name = isset($node->nodeName) ? $node->nodeName : '';
        if($name == 'img'){
            $this->rewrite_path($node, 'src', $question);
        }else if($name == 'object'){
            $this->rewrite_path($node, 'data', $question);
        }

        $this->translate_nodes($node->childNodes, $question);
    }

    private function translate_nodes($nodes, $question){
        if(empty($nodes)){
            return;
        }
        for($i = 0, $length = $nodes->length; $i<$length; $i++){
            $node = $nodes->item($i);
            $this->translate_node($node, $question);
        }
    }

    private function rewrite_path($node, $attribute, $question){
        if(!$node->hasAttribute($attribute)) return;
        $path = $node->getAttribute($attribute);
        $path = str_replace('@@PLUGINFILE@@', $question->id . '/'. $question->category, $path);
        $path = $this->resource_manager->translate_path($path);
        $node->setAttribute($attribute, $path);
    }


}

/**
 * Base class for questions serializers and subquestion serializers.
 * subquestion serializers are used by the MULTIANSWER question's type.
 *
 * University of Geneva
 * @author laurent.opprecht@unige.ch
 *
 */
class QuestionSerializerBase extends SerializerBase{

    /*
    static function accept_file($path){
        if(!is_file($path)){
            return false;
        }
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $result = $ext == 'php' && basename($path) != basename(__FILE__);
        return $result;
    }

    static function class_name($path){
        $ext = pathinfo($path, PATHINFO_EXTENSION);
        $name = basename($path, $ext);
        $name = str_replace('.class', '', $name);
        $name = str_replace('.', '', $name);
        $parts = explode('_', $name);
        $result = array();
        foreach($parts as $part){
            $result[] = ucfirst($part);
        }
        $result = implode('', $result);
        return $result;
    }
*/


    protected function has_answer_feedback($question){
        if(!isset($question->options) || !isset($question->options->answers) || !is_array($question->options->answers)){
            return false;
        }
        foreach($question->options->answers as $answer){
            if(isset($answer->feedback) && !empty($answer->feedback)){
                return true;
            }
        }
        return false;
    }

}

/**
 * Base clase for sub questions embedded in a multi-answer/cloze parent question.
 *
 * University of Geneva
 * @author laurent.opprecht@unige.ch
 *
 */
class SubquestionSerializer extends QuestionSerializerBase{

    public function __construct($resource_manager){
        parent::__construct($resource_manager);
    }

    public function add_feedback_declaration(ImsQtiWriter $item, $question){
        if($this->has_answer_feedback($question)){
            $id = $this->feedback_id($question);
            $result = $item->add_outcomeDeclaration_feedback($id);
            $result->add_defaultValue()->add_value('DEFAULT_FEEDBACK');
            return $result;
        }else{
            return null;
        }
    }

    public function add_feedback(ImsQtiWriter $item, $question){
        return null;
    }

    public function add_feedback_processing(ImsQtiWriter $processing, $question){
        return null;
    }

    public function add_response_declaration(ImsQtiWriter $item, $question){
        $cardinality = ImsQtiWriter::CARDINALITY_SINGLE;
        $identity = $this->response_id($question);
        $result = $item->add_responseDeclaration($identity, $cardinality, ImsQtiWriter::BASETYPE_STRING);

        return $result;
    }

    public function add_score_declaration(ImsQtiWriter $item, $question){
        $score = $question->defaultgrade;
        $cardinality = ImsQtiWriter::CARDINALITY_SINGLE;
        $id = $this->score_id($question);
        $base_type = is_int($score) ? ImsQtiWriter::BASETYPE_INTEGER : ImsQtiWriter::BASETYPE_FLOAT;
        $result = $item->add_outcomeDeclaration($id, $cardinality, $base_type, $score);
        $result->add_defaultValue()->add_value(0);
        return $result;
    }

    public function add_score_processing(ImsQtiWriter $response_processing, $question){
        return null;
    }

    public function add_interaction(ImsQtiWriter $item, $question){
        return null;
    }

    public function score_id($question){
        return ImsQtiWriter::SCORE . '_' .$question->id;
    }

    public function response_id($question){
        return ImsQtiWriter::RESPONSE . '_' .$question->id;
    }

    public function feedback_id($question){
        return ImsQtiWriter::FEEDBACK . '_'. $question->id;
    }

    public function answer_id($answer){
        return 'ANSWER_'. $answer->id;
    }
}


/**
 * Empty object pattern for subquestion serializers.
 * Do nothing.
 *
 * University of Geneva
 * @author laurent.opprecht@unige.ch
 *
 */
class SubquestionSerializerEmpty{

    public function __construct(){
    }

    public function add_feedback_declaration(ImsQtiWriter $item, $question){
        return null;
    }

    public function add_feedback(ImsQtiWriter $item, $question){
        return null;
    }

    public function add_feedback_processing(ImsQtiWriter $processing, $question){
        return null;
    }

    public function add_response_declaration(ImsQtiWriter $item, $question){
        return null;
    }

    public function add_score_declaration(ImsQtiWriter $item, $question){
        return null;
    }

    public function add_score_processing(ImsQtiWriter $response_processing, $question){
        return null;
    }

    public function add_interaction(ImsQtiWriter $item, $question){
        return null;
    }

    public function score_id($question){
        return '';
    }

    public function response_id($question){
        return '';
    }

    public function feedback_id($question){
        return '';
    }

    public function answer_id($answer){
        return '';
    }
}


