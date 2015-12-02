<?php

//require_once dirname(dirname(__FILE__)) .'/main.php';

/**
 * Serializer for true-false questions.
 *
 * University of Geneva
 * @author laurent.opprecht@unige.ch
 *
 */
class TrueFalseSerializer extends QuestionSerializer{

    static function factory($question, $target_root){
        if($question->qtype != 'truefalse'){
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

    protected function add_response_declaration(ImsQtiWriter $item, $question){
        $result = parent::add_response_declaration($item, $question);
        $correct_response = $result->add_correctResponse();
        $mapping = $result->add_mapping();

        foreach($question->options->answers as $answer){
            $identifier = $answer->answer;
            if($is_correct = $answer->fraction == 1){
                $correct_response->add_value($identifier);
            }
            $mapping->add_mapEntry($identifier, $answer->fraction * $question->defaultgrade);
        }
        return $result;
    }

    protected function add_score_processing(ImsQtiWriter $response_processing, $question){
        return $response_processing->add_standard_response_map_response();
    }

    protected function add_answer_feedback(ImsQtiWriter $item, $question){
        foreach($question->options->answers as $answer){
            $identifier = $answer->answer;
            if($has_feeback = !empty($answer->feedback)){
                $text = $this->translate_feedback_text($answer->feedback, self::FORMAT_HTML, $question);
                $item->add_modalFeedback(ImsQtiWriter::FEEDBACK, $identifier, 'show')->add_flow($text);
            }
        }
    }

    protected function add_interaction(ImsQtiWriter $body, $question){
        $result = $body->add_choiceInteraction();
        foreach($question->options->answers as $answer){
            $identifier = $answer->answer;
            $choice = $result->add_simpleChoice($identifier);
            $choice->add_flow($answer->answer);
        }
        return $result;
    }

}








