<?php

/**
 * Question builder for multichoice questions.
 *
 * University of Geneva
 * @author laurent.opprecht@unige.ch
 *
 */
class MultichoiceBuilder extends QuestionBuilder{

    static function factory(QtiImportSettings $settings){

        $item = $settings->get_reader();
        $category = $settings->get_category();

        //if it is a reimport
        if($data = $settings->get_data()){
            if($data->qtype == 'multichoice'){
                return new self($category);
            }else{
                return null;
            }
        }

        if( !self::has_score($item)){
            return null;
        }else{
            $count = count($item->list_interactions());
            $main = self::get_main_interaction($item);
            if($count == 1 && $main->is_choiceInteraction()){
                return new self($category);
            }else{
                return null;
            }
        }
    }

    public function create_question(){
        $result = parent::create_question();
        $result->qtype = 'multichoice';
        $result->fraction = array();
        $result->answer = array();
        $result->feedback = array();
        $result->answernumbering = 'none';
        $result->correctfeedback = '';
        $result->partiallycorrectfeedback = '';
        $result->incorrectfeedback = '';
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

        $feedbacks_to_filter_out = array_merge($general_feedbacks, $correct_feedbacks, $partiallycorrect_feedbacks, $incorrect_feedbacks);

        $interaction = self::get_main_interaction($item);
        $result->single = $interaction->maxChoices == 1;
        $result->shuffleanswers = $interaction->shuffle == 'true' || $interaction->shuffle == '';

        $result->defaultgrade = $this->get_maximum_score($item);

        $choices = $interaction->all_simpleChoice();
        foreach($choices as $choice){
            $answer = $choice->identifier;
            $result->answer[] = $this->to_text($choice);
            $result->feedback[] = $this->format_text($this->get_feedback($item, $interaction, $answer, $feedbacks_to_filter_out));
            $result->fraction[] = $this->get_fraction($item, $interaction, $answer, $result->defaultgrade);
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

        $result->single = $data->options->single;
        $result->shuffleanswers = $data->options->shuffleanswers;
        $result->answernumbering = $data->options->layout;

        $answers = $data->options->answers;
        foreach($answers as $a){
            $result->answer[] = $a->answer;
            $result->feedback[] = $this->format_text($a->feedback);
            $result->fraction[] = $a->fraction;
        }

        return $result;
    }

}








