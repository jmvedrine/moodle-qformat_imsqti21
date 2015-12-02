<?php

//require_once dirname(dirname(__FILE__)) .'/main.php';

/**
 * Serializer for calculated multi-choices questions.
 *
 * University of Geneva
 * @author laurent.opprecht@unige.ch
 *
 */
class MultiplechoiceSerializer extends QuestionSerializer{

    const PARTIAL_SCORE = 'PARTIAL_SCORE';
    const OVERALL_FEEDBACK = 'OVERALL_FEEDBACK';
    const FEEDBACK_CORRECT = 'FEEDBACK_CORRECT';
    const FEEDBACK_INCORRECT = 'FEEDBACK_INCORRECT';
    const FEEDBACK_PARTIALY_CORRECT = 'FEEDBACK_PARTIALY_CORRECT';

    static function factory($question, $target_root){
        if(!defined("MULTICHOICE") || $question->qtype != MULTICHOICE){
            return null;
        }else{
            return new self($target_root);
        }
    }

    static function factory_subquestion($question, $resource_manager){
        if(!defined("MULTICHOICE") || $question->qtype != MULTICHOICE){
            return new SubquestionSerializerEmpty();
        }else{
            return new MultipleChoiceSubquestionSerializer($resource_manager);
        }
    }

    public function __construct($target_root){
        parent::__construct($target_root);
    }

    public function add_response_declaration($item, $question){
        $cardinality = $question->options->single ? ImsQtiWriter::CARDINALITY_SINGLE : ImsQtiWriter::CARDINALITY_MULTIPLE;
        $identity = ImsQtiWriter::RESPONSE;
        $result = $item->add_responseDeclaration($identity, $cardinality, ImsQtiWriter::BASETYPE_IDENTIFIER);
        $correct_response = $result->add_correctResponse();
        $mapping = $result->add_mapping();

        foreach($question->options->answers as $answer){
            $identifier = 'ID_' . $answer->id;
            if($is_correct = $answer->fraction == 1){
                $correct_response->add_value($identifier);
            }
            $mapping->add_mapEntry($identifier, $answer->fraction * $question->defaultgrade);
        }

        return $result;
    }

    protected function add_outcome_declaration(ImsQtiWriter $item, $question){
        $result = parent::add_outcome_declaration($item, $question);

        $this->add_overall_feedback_declaration($item, $question);
        $this->add_partial_score_declaration($item, $question);
        return $result;
    }

    public function add_overall_feedback_declaration(ImsQtiWriter $item, $question){
        $identifier = self::OVERALL_FEEDBACK;
        $result = $item->add_outcomeDeclaration_feedback($identifier);
        return $result;
    }

    protected function add_partial_score_declaration($item, $question){
        $score = $question->defaultgrade;
        $cardinality = ImsQtiWriter::CARDINALITY_SINGLE;
        $name = self::PARTIAL_SCORE;
        $base_type = is_int($score) ? ImsQtiWriter::BASETYPE_INTEGER : ImsQtiWriter::BASETYPE_FLOAT;
        $result = $item->add_outcomeDeclaration($name, $cardinality, $base_type, $score);
        $result->add_defaultValue()->add_value(0);
        return $result;
    }

    protected function add_response_processing($item, $question){
        $result = parent::add_response_processing($item, $question);
        $this->add_overall_feedback_processing($result, $question);
        return $result;
    }

    protected function add_overall_feedback_processing(ImsQtiWriter $processing, $question){
        $processing->add_standard_response_map_response(ImsQtiWriter::RESPONSE, self::PARTIAL_SCORE);
        $result = $processing->add_responseCondition();
        $if = $result->add_responseIf();
        $lte = $if->add_lte();
        $lte->add_variable(self::PARTIAL_SCORE);
        $lte->add_baseValue(ImsQtiWriter::BASETYPE_FLOAT, 0);
        $if->add_setOutcomeValue(self::OVERALL_FEEDBACK)->add_baseValue(ImsQtiWriter::BASETYPE_IDENTIFIER, self::FEEDBACK_INCORRECT);
        $elseif = $result->add_responseElseIf();
        $gte = $elseif->add_gte();
        $gte->add_variable(self::PARTIAL_SCORE);
        $gte->add_baseValue(ImsQtiWriter::BASETYPE_FLOAT, $question->defaultgrade);
        $elseif->add_setOutcomeValue(self::OVERALL_FEEDBACK)->add_baseValue(ImsQtiWriter::BASETYPE_IDENTIFIER, self::FEEDBACK_CORRECT);
        $else = $result->add_responseElse();
        $else->add_setOutcomeValue(self::OVERALL_FEEDBACK)->add_baseValue(ImsQtiWriter::BASETYPE_IDENTIFIER, self::FEEDBACK_PARTIALY_CORRECT);
        return $result;
    }

    protected function add_score_processing($response_processing, $question){
        return $response_processing->add_standard_response_map_response();
    }

    protected function add_modal_feedback($item, $question){
        $result = parent::add_modal_feedback($item, $question);
        $this->add_overall_feedback($item, $question);
        return $result;
    }

    protected function add_answer_feedback(ImsQtiWriter $item, $question){
        foreach($question->options->answers as $answer){
          $id = 'ID_'. $answer->id;
          if($has_feeback = !empty($answer->feedback)){
            $text = $this->translate_feedback_text($answer->feedback, self::FORMAT_HTML, $question);
            $item->add_modalFeedback(ImsQtiWriter::FEEDBACK, $id, 'show')->add_flow($text);
          }
        }
    }

    protected function add_overall_feedback(ImsQtiWriter $item, $question){
        $text = $this->translate_feedback_text($question->options->incorrectfeedback, self::FORMAT_HTML, $question);
        $item->add_modalFeedback(self::OVERALL_FEEDBACK, self::FEEDBACK_INCORRECT, 'show')->add_flow($text);

        $text = $this->translate_feedback_text($question->options->correctfeedback, self::FORMAT_HTML, $question);
        $item->add_modalFeedback(self::OVERALL_FEEDBACK, self::FEEDBACK_CORRECT, 'show')->add_flow($text);

        $text = $this->translate_feedback_text($question->options->partiallycorrectfeedback, self::FORMAT_HTML, $question);
        $item->add_modalFeedback(self::OVERALL_FEEDBACK, self::FEEDBACK_PARTIALY_CORRECT, 'show')->add_flow($text);
    }

    protected function add_interaction($body, $question){
        $max_choices = $question->options->single ? 1 : 0;
        $shuffle = $question->options->shuffleanswers;
        $result = $body->add_choiceInteraction(ImsQtiWriter::RESPONSE, $max_choices, $shuffle);

        foreach($question->options->answers as $answer){
            $identifier = 'ID_' . $answer->id;
            $result->add_simpleChoice($identifier)->add_flow($answer->answer);
        }
        return $result;
    }
}


/**
 * Used to serialize children questions embeded in a multi-answer/cloze parent question.
 * @author lo
 *
 */
class MultipleChoiceSubquestionSerializer extends SubquestionSerializer{

    public function __construct($resource_manager){
        parent::__construct($resource_manager);
    }

    public function add_feedback(ImsQtiWriter $item, $question){
        $feedback_id = $this->feedback_id($question);
        foreach($question->options->answers as $answer){
            $answer_id = $this->answer_id($answer);
            if($has_feeback = !empty($answer->feedback)){
                $text = $this->translate_feedback_text($answer->feedback, self::FORMAT_HTML, $question);
                $item->add_modalFeedback($feedback_id, $answer_id, 'show')->add_flow($text);
            }
        }
    }

    public function add_feedback_processing(ImsQtiWriter $processing, $question){
        if($this->has_answer_feedback($question)){
            $response_id = $this->response_id($question);
            $feedback_id = $this->feedback_id($question);
            $result = $processing->add_standard_response_assign_feedback($response_id, $feedback_id);
            return $result;
        }else{
            return null;
        }
    }

    public function add_response_declaration(ImsQtiWriter $item, $question){
        $cardinality = $question->options->single ? ImsQtiWriter::CARDINALITY_SINGLE : ImsQtiWriter::CARDINALITY_MULTIPLE;
        $response_id = $this->response_id($question);
        $result = $item->add_responseDeclaration($response_id, $cardinality, ImsQtiWriter::BASETYPE_IDENTIFIER);
        $correct_response = $result->add_correctResponse();
        $mapping = $result->add_mapping();

        foreach($question->options->answers as $answer){
            $answer_id = $this->answer_id($answer);
            if($is_correct = $answer->fraction == 1){
                $correct_response->add_value($answer_id);
            }
            $mapping->add_mapEntry($answer_id, $answer->fraction * $question->defaultgrade);
        }

        return $result;
    }

    public function add_score_processing(ImsQtiWriter $response_processing, $question){
        $response_id = $this->response_id($question);
        $score_id = $this->score_id($question);
        return $response_processing->add_standard_response_map_response($response_id, $score_id);
    }

    public function add_interaction(ImsQtiWriter $item, $question){
        $shuffle = $question->options->shuffleanswers;
        $response_id = $this->response_id($question);
        $result = $item->add_inlineChoiceInteraction($response_id, $shuffle);

        foreach($question->options->answers as $answer){
            $answer_id = $this->answer_id($answer);
            $result->add_inlineChoice($answer_id)->add_flow($answer->answer);
        }
        return $result;
    }

}








