<?php

/**
 * Base class for numerical questions.
 *
 * University of Geneva
 * @author laurent.opprecht@unige.ch
 *
 */
class NumericalBuilderBase extends QuestionBuilder{

    const UNIT_TEXTBOX = 0;
    const UNIT_RADIO = 1;
    const UNIT_READONLY = 2;
    const UNIT_HIDE = 3;
    const UNIT_MULTIPLIER_IDENTIFIER = 'UNIT_MULTIPLIER';
    const QUESTION_GRADE = 1;
    const TOLERANCE_TYPE_RELATIVE = 1;
    const ANSWER_FORMAT_DECIMAL = 1;

    protected function get_unit_interaction(ImsXmlReader $item){
        $interactions = $item->list_interactions();
        $main = self::get_main_interaction($item);
        if(count($interactions)==2){
            return $interactions[0]->responseIdentifier == $main->responseIdentifier ? $interactions[1] : $interactions[0];
        }else{
            return $item->get_default_result();
        }
    }

    protected function get_showunits(ImsXmlReader $item){
        $unit = $this->get_unit_interaction($item);
        if($unit->is_textEntryInteraction() || $unit->is_extendedTextInteraction()){
            $result = self::UNIT_TEXTBOX;
        }else if($unit->is_choiceInteraction()){
            $result = self::UNIT_RADIO;
        }else{
            $result = self::UNIT_HIDE;
        }
        return $result;
    }

    protected function get_units(ImsXmlReader $item){
        $unit = $this->get_unit_interaction($item);
        if($unit->is_empty()){
            return array();
        }else{
            return $this->get_possible_responses_text($item, $unit);
        }
    }

    protected function get_multipliers(ImsXmlReader $item){
        $result = array();
        $unit = $this->get_unit_interaction($item);
        if($unit->is_empty()){
            return array();
        }
        $responses = $this->get_possible_responses($item, $unit);
        foreach($responses as $response){
            $multiplier = $this->get_outcome($item, $unit, $response, self::UNIT_MULTIPLIER_IDENTIFIER);
            $multiplier = empty($multiplier) ? 1 : $multiplier;
            $result[] = $multiplier;
        }
        return $result;
    }

    protected function get_unitsleft(ImsXmlReader $item){
        $main_id = $this->get_main_interaction($item)->responseIdentifier;
        $unit_id = $this->get_unit_interaction($item)->responseIdentifier;
        if(empty($unit_id)){
            return false;
        }
        $body = $item->get_itemBody();
        foreach($body as $element){
            if($element->responseIdentifier == $main_id){
                return false;
            }
            if($element->responseIdentifier == $unit_id){
                return true;
            }
        }
        return false;
    }

    protected function get_unitpenalty(ImsXmlReader $item){
        return 0;
    }

    protected function get_unitgradingtype(ImsXmlReader $item){
        return self::QUESTION_GRADE;
    }

}