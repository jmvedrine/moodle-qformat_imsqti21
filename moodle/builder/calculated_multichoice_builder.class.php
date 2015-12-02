<?php

/**
 * Question builder for calculated multichoice questions.
 *
 * University of Geneva
 * @author laurent.opprecht@unige.ch
 *
 */
class CalculatedMultichoiceBuilder extends CalculatedBuilderBase{

    static function factory(QtiImportSettings $settings){

        $item = $settings->get_reader();
        $category = $settings->get_category();

        //if it is a reimport
        if($data = $settings->get_data()){
            if($data->qtype == 'calculatedmulti'){
                return new self($category);
            }else{
                return null;
            }
        }

        if(count($item->list_interactions())>1 || !self::has_score($item)){
            return null;
        }
        if(!self::is_calculated($item)){
            return null;
        }
        $main = self::get_main_interaction($item);
        if( $main->is_choiceInteraction()){
            return new self($category);
        }else{
            return false;
        }
    }

    public function create_question(){
        $result = parent::create_question();
        $result->qtype = 'calculatedmulti';
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

        $result->answernumbering = 'none';
        $result->correctfeedback = '';
        $result->partiallycorrectfeedback = '';
        $result->incorrectfeedback = '';

        $result->single = true;
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
        $result->name = $this->get_question_title($item);
        $result->questiontext = $this->get_question_text($item);
        $result->penalty = $this->get_penalty($item);

        $general_feedbacks = $this->get_general_feedbacks($item);
        $result->generalfeedback = implode('<br/>', $general_feedbacks);

        $correct_feedbacks = $this->get_correct_feedbacks($item, $general_feedbacks);
        $result->correctfeedback = $this->format_text(implode('<br/>', $correct_feedbacks));

        $partiallycorrect_feedbacks = $this->get_partiallycorrect_feedbacks($item, $general_feedbacks);
        $result->partiallycorrectfeedback = $this->format_text(implode('<br/>', $partiallycorrect_feedbacks));

        $incorrect_feedbacks = $this->get_incorrect_feedbacks($item, $general_feedbacks);
        $result->incorrectfeedback = $this->format_text(implode('<br/>', $incorrect_feedbacks));

        $feedbacks_to_filter_out = array_merge($general_feedbacks, $correct_feedbacks, $partiallycorrect_feedbacks, $incorrect_feedbacks);

        $result->dataset = $this->get_datasets($item);
        $result->defaultgrade = $this->get_maximum_score($item);

        $interaction = self::get_main_interaction($item);
        $result->single = $interaction->maxChoices == 1;
        $result->shuffleanswers = $interaction->shuffle == 'true' || $interaction->shuffle == '';

        $correct_responses = $this->get_correct_responses($item, $interaction);
        $choices = $interaction->all_simpleChoice();
        foreach($choices as $choice){
            $answer = $choice->identifier;
            $result->answers[] = $this->to_text($choice);
            $result->feedback[] = $this->format_text($this->get_feedback($item, $interaction, $answer, $feedbacks_to_filter_out));
            $result->fraction[] = $this->get_fraction($item, $interaction, $answer, $result->defaultgrade);
            $result->tolerance[] = 0;
            $result->tolerancetype[] = self::TOLERANCE_TYPE_RELATIVE;
            $result->correctanswerformat[] = $this->get_correctanswerformat($item, $interaction, $answer);
            $result->correctanswerlength[] = $this->get_correctanswerlength($item, $interaction, $answer);
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
















