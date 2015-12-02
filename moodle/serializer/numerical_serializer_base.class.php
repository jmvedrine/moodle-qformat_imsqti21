<?php

//require_once dirname(dirname(__FILE__)) .'/main.php';

/**
 * Base serializer for numerical questions.
 *
 * University of Geneva
 * @author laurent.opprecht@unige.ch
 *
 */
class NumericalSerializerBase extends QuestionSerializer{

    const UNIT = 'UNIT';
    const UNIT_MULTIPLIER = 'UNIT_MULTIPLIER';
    const UNIT_TEXTBOX = 0;
    const UNIT_RADIO = 1;
    const UNIT_READONLY = 2;
    const UNIT_HIDE = 3;

    const OVERALL_FEEDBACK = 'OVERALL_FEEDBACK';
    const FEEDBACK_CORRECT = 'FEEDBACK_CORRECT';
    const FEEDBACK_INCORRECT = 'FEEDBACK_INCORRECT';
    const FEEDBACK_PARTIALY_CORRECT = 'FEEDBACK_PARTIALY_CORRECT';

    public function __construct($target_root){
        parent::__construct($target_root);
    }

    protected function add_outcome_declaration($item, $question){
        $result = parent::add_outcome_declaration($item, $question);
        $this->add_overall_feedback_declaration($item, $question);

        $cardinality = ImsQtiWriter::CARDINALITY_SINGLE;
        $name = self::UNIT_MULTIPLIER;
        $base_type = ImsQtiWriter::BASETYPE_FLOAT ;
        $score_outcome = $item->add_outcomeDeclaration($name, $cardinality, $base_type);
        $score_outcome->add_defaultValue()->add_value(1);

        return $result;
    }

    protected function add_overall_feedback_declaration(ImsQtiWriter $item, $question){
        $identifier = self::OVERALL_FEEDBACK;
        $result = $item->add_outcomeDeclaration_feedback($identifier);
        return $result;
    }

    protected function add_response_declaration(ImsQtiWriter  $item, $question){
        //main response
        $cardinality =  ImsQtiWriter::CARDINALITY_SINGLE;
        $base_type = ImsQtiWriter::BASETYPE_FLOAT;
        $result = $item->add_responseDeclaration(ImsQtiWriter::RESPONSE, $cardinality, $base_type);
        $correct_response = $result->add_correctResponse();
        foreach($question->options->answers as $answer){
            if($answer->fraction == 1){
                $correct_response->add_value($answer->answer);
            }
        }

        $this->add_unit_declaration($item, $question);
        return $result;
    }

    protected function add_unit_declaration(ImsQtiWriter $item, $question){
        //units response
        $show_units = $question->options->showunits;
        if($show_units == self::UNIT_TEXTBOX || $show_units == self::UNIT_RADIO){
            $cardinality =  ImsQtiWriter::CARDINALITY_SINGLE;
            $base_type = ImsQtiWriter::BASETYPE_STRING;
            $result = $item->add_responseDeclaration(self::UNIT, $cardinality, $base_type);
            $mapping = $result->add_mapping('', '', 1);
            foreach($question->options->units as $unit){
                $mapping->add_mapEntry($unit->unit, empty($unit->multiplier) ? 0 : $unit->multiplier);
            }

        }else{
            $result = null;
        }
        return $result;
    }

    protected function add_unit_processing(ImsQtiWriter $response_processing, $question){
        $show_units = $question->options->showunits;
        if($show_units == self::UNIT_HIDE || $show_units == self::UNIT_READONLY){
            return null;
        }
        $response = self::UNIT;
        $outcome = self::UNIT_MULTIPLIER;

        $result = $response_processing->add_responseCondition();
        $if = $result->add_responseIf();
        $if->add_isNull()->add_variable($response);
        $if->add_setOutcomeValue($outcome)->add_baseValue(ImsQtiWriter::BASETYPE_FLOAT, 1);
        $result->add_responseElse()->add_setOutcomeValue($outcome)->add_mapResponse($response);
        return $result;
    }

    protected function add_answer_feedback(ImsQtiWriter $item, $question){
        $count = 0;
        foreach($question->options->answers as $answer){
            ++$count;
            $id = $answer->answer == '*' ? 'FEEDBACK_ID_ELSE' : 'FEEDBACK_ID_'. $count;
            if($has_feeback = !empty($answer->feedback)){
                $text = $this->translate_feedback_text($answer->feedback, self::FORMAT_HTML, $question);
                $item->add_modalFeedback(ImsQtiWriter::FEEDBACK, $id, 'show')->add_flow($text);
            }
        }
    }

    protected function add_interaction(ImsQtiWriter $body, $question){
        if($question->options->unitsleft){
            $this->add_unit($body, $question);
            $result = $body->add_textEntryInteraction();
        }else{
            $result = $body->add_textEntryInteraction();
            $this->add_unit($body, $question);
        }
        $instructions = $question->options->instructions;
        if(!empty($instructions)){
            $body->add_rubricBlock(ImsQtiWriter::VIEW_ALL)->add_flow($instructions);
        }
        return $result;
    }

    protected function add_unit(ImsQtiWriter $body, $question){
        switch($question->options->showunits){
            case self::UNIT_TEXTBOX: //Edit unit Editable text input element
                $expected_length = 1;
                foreach($question->options->units as $unit){
                    $expected_length = max($expected_length, strlen($unit->unit));
                }
                return $body->add_extendedTextInteraction(self::UNIT, $expected_length, 1, 1);
            case self::UNIT_RADIO: //Select units Choice radio element of 2 Units minimum
                $result = $body->add_choiceInteraction(self::UNIT, 1, false);
                foreach($question->options->units as $unit){
                    $choice = $result->add_simpleChoice($unit->unit);
                    $choice->add_span($unit->unit);
                }
                return $result;
            case self::UNIT_READONLY: //Display unit NON editable text of Unit No1
                if(isset($question->options->units[0])){
                    $result = $body->add_span($question->options->units[0]->unit);
                }else{
                    $result = null;
                }
                return $result;
            case self::UNIT_HIDE: //No unit display Only numerical answer will be graded
                //do nothing
                return null;
        }
    }

    protected function add_modal_feedback($item, $question){
        $result = parent::add_modal_feedback($item, $question);
        $this->add_overall_feedback($item, $question);
        return $result;
    }

    protected function add_overall_feedback(ImsQtiWriter $item, $question){
        if(!isset($question->options) || !isset($question->options->incorrectfeedback)){
            return false;
        }

        $text = $this->translate_feedback_text($question->options->incorrectfeedback, self::FORMAT_HTML, $question);
        $item->add_modalFeedback(self::OVERALL_FEEDBACK, self::FEEDBACK_INCORRECT, 'show')->add_flow($text);

        $text = $this->translate_feedback_text($question->options->correctfeedback, self::FORMAT_HTML, $question);
        $item->add_modalFeedback(self::OVERALL_FEEDBACK, self::FEEDBACK_CORRECT, 'show')->add_flow($text);

        $text = $this->translate_feedback_text($question->options->partiallycorrectfeedback, self::FORMAT_HTML, $question);
        $item->add_modalFeedback(self::OVERALL_FEEDBACK, self::FEEDBACK_PARTIALY_CORRECT, 'show')->add_flow($text);
    }
}








