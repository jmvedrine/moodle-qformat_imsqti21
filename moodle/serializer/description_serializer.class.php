<?php

//require_once dirname(dirname(__FILE__)) .'/main.php';

/**
 * Serializer for description questions.
 *
 * University of Geneva
 * @author laurent.opprecht@unige.ch
 *
 */
class DescriptionSerializer extends QuestionSerializer{

    static function factory($question, $target_root){
        if($question->qtype != 'description'){
            return null;
        }else{
            return new self($target_root);
        }
    }

    static function factory_subquestion($question, $resource_manager){
        return new SubquestionSerializerEmpty();
    }

    public function __construct($target_root){
        parent::__construct($target_root);
    }

    protected function add_score_declaration(ImsQtiWriter $item, $question){
        return null;
    }

    protected function add_response_declaration(ImsQtiWriter $item, $question){
        return null;
    }

    protected function add_response_processing(ImsQtiWriter  $item, $question){
        return false;
    }

    protected function add_outcome_declaration(ImsQtiWriter $item, $question){
        return $this->add_general_feedback_declaration($item, $question);
    }
}








