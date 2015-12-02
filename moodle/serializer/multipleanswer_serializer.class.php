<?php

//require_once dirname(dirname(__FILE__)) .'/main.php';

/**
 * Serializer for multiple-answers/cloze questions.
 *
 * University of Geneva
 * @author laurent.opprecht@unige.ch
 *
 */
class MultipleAnswerSerializer extends QuestionSerializer{

    static function factory($question, $target_root){
        if(!defined("MULTIANSWER") || $question->qtype != MULTIANSWER){
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

    protected function serializer($subquestion){
        return QuestionSerializer::factory_subquestion($subquestion, $this->get_resource_manager());
    }

    protected function add_outcome_declaration($item, $question){
        $result = parent::add_outcome_declaration($item, $question);
        foreach($question->options->questions as $subquestion){
            $this->serializer($subquestion)->add_score_declaration($item, $subquestion);
        }
        foreach($question->options->questions as $subquestion){
            $this->serializer($subquestion)->add_feedback_declaration($item, $subquestion);
        }
        return $result;
    }

    protected function add_response_declaration($item, $question){
        foreach($question->options->questions as $subquestion){
            $result = $this->serializer($subquestion)->add_response_declaration($item, $subquestion);
        }
        return $result;
    }

    protected function add_score_processing(ImsQtiWriter $response_processing, $question){
        foreach($question->options->questions as $subquestion){
            $this->serializer($subquestion)->add_score_processing($response_processing, $subquestion);
        }
        $result = $response_processing->add_setOutcomeValue(ImsQtiWriter::SCORE);
        $sum = $result->add_sum();
        foreach($question->options->questions as $subquestion){
            if($id = $this->serializer($subquestion)->score_id($subquestion)){
                $sum->add_variable($id);
            }
        }
        return $result;
    }

    protected function add_answer_feedback(ImsQtiWriter $item, $question){
        foreach($question->options->questions as $subquestion){
            $this->serializer($subquestion)->add_feedback($item, $subquestion);
        }
    }

    protected function add_response_processing($item, $question){
        $result = parent::add_response_processing($item, $question);
        foreach($question->options->questions as $subquestion){
            $this->serializer($subquestion)->add_feedback_processing($result, $subquestion);
        }
        return $result;
    }

    protected function add_body(ImsQtiWriter $item, $question){
        $result = $item->add_itemBody();
        $text = $this->translate_question_text($question->questiontext, $question->questiontextformat, $question);
        foreach($question->options->questions as $index=>$subquestion){
            $subquestion_id = "{#$index}";
            $subquestion_xml = $this->get_subquestion_xml($subquestion);
            $text = str_replace($subquestion_id, $subquestion_xml, $text);
        }
        $result->add_xml($text);
        return $result;
    }

    protected function get_subquestion_xml($subquestion){
        $serializer = $this->serializer($subquestion);
        $writer = new ImsQtiWriter();
        $this->serializer($subquestion)->add_interaction($writer, $subquestion);
        $result = $writer->saveXML(false);
        return $result;

    }

    protected function add_interaction($body, $question){
        return null;
    }

    /**
     * Returns data to be serialized on top of the QTI format.
     * Made of the question's object minus fields that don't have a meaning in another system.
     *
     * Remove question's fields which don't have a meaning in another system.
     * For example question id, user id, etc
     *
     * @param object $question
     * @return object
     */
    protected function get_question_data($question){
        $result = parent::get_question_data($question);
        return $result;
    }

}








