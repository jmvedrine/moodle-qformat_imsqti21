<?php

/**
 * Question builder for claculated questions.
 *
 * University of Geneva
 * @author laurent.opprecht@unige.ch
 *
 */
class CalculatedBuilder extends CalculatedBuilderBase{

    static function factory(QtiImportSettings $settings){

        $item = $settings->get_reader();
        $category = $settings->get_category();

        //if it is a reimport
        if($data = $settings->get_data()){
            if($data->qtype == 'calculated'){
                return new self($category);
            }else{
                return null;
            }
        }

        if(count($item->list_interactions())>2 || !self::has_score($item)){
            return null;
        }
        if(!self::is_calculated($item)){
            return null;
        }

        $interactions = $item->list_interactions();
        $main = self::get_main_interaction($item);
        if(count($interactions)==2){
            $second = $interactions[0] == $main ? $interactions[1] : $interactions[0];
            if(strtoupper($second->responseIdentifier) != 'UNIT'){
                return null;
            }
        }
        if(! self::is_numeric_interaction($item, $main)){
            return null;
        }
        return new self($category);
    }

    public function create_question(){
        $result = parent::create_question();
        $result->qtype = 'calculated';
        $result->answers = array();
        $result->feedback = array();
        $result->fraction = array();
        $result->tolerance = array();
        $result->tolerancetype = array();
        $result->correctanswerformat = array();
        $result->correctanswerlength = array();

        $result->generalfeedback = '';
        $result->synchronize = false;
        $result->dataset = array();
        $result->length = 1;
        $result->showunits = self::UNIT_HIDE;
        $result->unitpenalty = 0;
        $result->unitsleft = false;
        $result->unitgradingtype = self::QUESTION_GRADE;
        $result->unit = array();
        $result->multiplier = array();
        $result->instructions = '';
        return $result;
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
        $result->penalty = $this->get_penalty($item);

        $general_feedbacks = $this->get_general_feedbacks($item);
        $correct_feedbacks = $this->get_correct_feedbacks($item, $general_feedbacks);
        $partiallycorrect_feedbacks = $this->get_partiallycorrect_feedbacks($item, $general_feedbacks);
        $incorrect_feedbacks = $this->get_incorrect_feedbacks($item, $general_feedbacks);

        $result->generalfeedback = implode('<br/>', $general_feedbacks);

        $result->correctfeedback = $this->format_text(implode('<br/>', $correct_feedbacks));
        $result->partiallycorrectfeedback = $this->format_text(implode('<br/>', $partiallycorrect_feedbacks));
        $result->incorrectfeedback = $this->format_text(implode('<br/>', $incorrect_feedbacks));

        $result->dataset = $this->get_datasets($item);
        $result->showunits = $this->get_showunits($item);
        $result->unit = $this->get_units($item);
        $result->multiplier = $this->get_multipliers($item);
        $result->unitpenalty = $this->get_unitpenalty($item);
        $result->unitsleft = $this->get_unitsleft($item);
        $result->unitgradingtype = $this->get_unitgradingtype($item);
        $result->instructions = $this->format_text($this->get_instruction($item));
        $result->defaultgrade = $this->get_maximum_score($item);

        $interaction = $this->get_main_interaction($item);
        $formulas = $this->get_score_formulas($item, $interaction);
        foreach($formulas as $formula){
            $result->answers[] = $this->render_formula($formula);
            $result->feedback[] = $this->format_text($this->get_feedback($item, $interaction, $formula, $general_feedbacks));
            $result->fraction[] = $this->get_fraction($item, $interaction, $formula, $result->defaultgrade);
            $result->tolerance[] = $this->get_tolerance($item, $interaction, $formula);
            $result->tolerancetype[] = $this->get_tolerancetype($item, $interaction, $formula);
            $result->correctanswerformat[] = $this->get_correctanswerformat($item, $interaction, $formula);
            $result->correctanswerlength[] = $this->get_correctanswerlength($item, $interaction, $formula);
        }
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

        $result->dataset = $data->options->datasets;
        $result->single = $data->options->single;
        $result->shuffleanswers = $data->options->shuffleanswers;
        $result->answernumbering = $data->options->answernumbering;
        $result->synchronize = $data->options->synchronize;

        $result->showunits = $data->options->showunits;
        $result->unitpenalty = $data->options->unitpenalty;
        $result->unitsleft = $data->options->unitsleft;
        $result->unitgradingtype = $data->options->unitgradingtype;
        $result->instructions = $data->options->instructions;

        $result->multiplier = array();
        $result->unit = array();
        $units = $data->options->units;
        foreach($units as $u){
            $result->multiplier[] = $u->multiplier;
            $result->unit[] = $u->unit;
        }

        $result->instructions = $this->format_text($result->instructions);

        $answers = $data->options->answers;
        foreach($answers as $a){
            $result->answers[] = $a->answer;
            $result->feedback[] = $this->format_text($a->feedback);
            $result->fraction[] = $a->fraction;
            $result->tolerance[] = $a->tolerance;
            $result->tolerancetype[] = $a->tolerancetype;
            $result->correctanswerformat[] = $a->correctanswerformat;
            $result->correctanswerlength[] = $a->correctanswerlength;
        }

        $datasets = $data->options->datasets;
        foreach($result->dataset as $ds){
            $ds->min = $ds->minimum;
            $ds->max = $ds->maximum;
            $ds->length = end(explode(':', $ds->options));
            $ds->datasetitem = $ds->items;
        }
        return $result;
    }

}









