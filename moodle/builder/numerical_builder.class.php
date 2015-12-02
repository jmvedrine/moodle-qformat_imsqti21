<?php

/**
 * Question builder for numerical questions.
 *
 * University of Geneva
 * @author laurent.opprecht@unige.ch
 *
 */
class NumericalBuilder extends NumericalBuilderBase{

    static function factory(QtiImportSettings $settings){

        $item = $settings->get_reader();
        $category = $settings->get_category();

        //if it is a reimport
        if($data = $settings->get_data()){
            if($data->qtype == 'numerical'){
                return new self($category);
            }else{
                return null;
            }
        }

        if(count($item->list_interactions())>2 || !self::has_score($item)){
            return null;
        }
        if(self::is_calculated($item)){
            return null;
        }
        $interactions = $item->list_interactions();
        $main = self::get_main_interaction($item);
        if(count($interactions)==2){
            $second = $interactions[0]->responseIdentifier == $main->responseIdentifier ? $interactions[1] : $interactions[0];
            if(strtoupper($second->responseIdentifier) != 'UNIT'){
                return null;
            }
        }
        if(! self::is_numeric_interaction($item, $main)){
            return null;
        }
        if(! self::has_answers($item, $main)){
            return null;
        }
        return new self($category);
    }

    public function create_question(){
        $result = parent::create_question();
        $result->qtype = 'numerical';
        $result->instructions = '';
        $result->answer = array();
        $result->fraction = array();
        $result->tolerance = array();
        $result->feedback = array();
        $result->generalfeedback = '';

        $result->showunits = self::UNIT_HIDE;
        $result->unitpenalty = 0;
        $result->unitsleft = false;
        $result->unitgradingtype = self::QUESTION_GRADE;
        $result->unit = array();
        $result->multiplier = array();

        return $result;
    }

    protected function get_answer($item, $answer){
        if($this->is_formula($answer)){
            return $this->execute_formula($item, $answer);
        }else{
            return $answer;
        }
    }

    /**
     * Build questions using the QTI format. Doing a projection by interpreting the file.
     *
     * @param QtiImportSettings $settings
     * @return object|null
     */
    public function build_qti(QtiImportSettings $settings){
        $item = $settings->get_reader();

        $result = $this->create_question();
        $result->name = $item->get_title();
        $result->questiontext =$this->get_question_text($item);

        $general_feedbacks = $this->get_general_feedbacks($item);
        $result->generalfeedback = implode('<br/>', $general_feedbacks);

        $result->showunits = $this->get_showunits($item);
        $result->unit = $this->get_units($item);
        $result->multiplier = $this->get_multipliers($item);
        $result->unitpenalty = $this->get_unitpenalty($item);
        $result->unitsleft = $this->get_unitsleft($item);
        $result->unitgradingtype = $this->get_unitgradingtype($item);
        $result->instructions = $this->format_text($this->get_instruction($item));
        $result->defaultgrade = $this->get_maximum_score($item);
        $result->penalty = $this->get_penalty($item);

        $interaction = self::get_main_interaction($item);
        $answers = $this->get_possible_responses($item, $interaction);
        foreach($answers as $answer){
            $result->answer[] = $this->get_answer($item, $answer);
            $result->fraction[] = $this->get_fraction($item, $interaction, $answer, $result->defaultgrade);
            $result->tolerance[] = $this->get_tolerance($item, $interaction, $answer);
            $result->feedback[] = $this->format_text($this->get_feedback($item, $interaction, $answer, $general_feedbacks));
        }

        //todo: * answers
        return $result;
    }

    /**
     * Build questions using moodle serialized data. Used for reimport, i.e. from Moodle to Moodle.
     * Used to process data not supported by QTI and to improve performances.
     *
     * @param QtiImportSettings $data
     * @return object|null
     */
    public function build_moodle(QtiImportSettings $settings){
        $data = $settings->get_data();

        $result = parent::build_moodle($settings);

        $result->instructions = $this->format_text($data->options->instructions);

        $result->showunits = $data->options->showunits;
        $result->unitsleft = $data->options->unitsleft;
        $result->unitgradingtype = $data->options->unitgradingtype;
        $result->unitpenalty = $data->options->unitpenalty;

        $result->multiplier = array();
        $result->unit = array();
        $units = $data->options->units;
        foreach($units as $u){
            $result->multiplier[] = $u->multiplier;
            $result->unit[] = $u->unit;
        }

        $answers = $data->options->answers;
        foreach($answers as $a){
            $result->answer[] = $a->answer;
            $result->fraction[] = $a->fraction;
            $result->tolerance[] = $a->tolerance;
            $result->feedback[] = $this->format_text($a->feedback);
        }

        return $result;
    }
}








