<?php

/**
 * Question builder for TRUEFALSE questions.
 *
 * University of Geneva
 * @author laurent.opprecht@unige.ch
 *
 */
class TruefalseBuilder extends QuestionBuilder{

    static function factory(QtiImportSettings $settings){
        if(!defined('TRUEFALSE')){
            return null;
        }
        $item = $settings->get_reader();
        $category = $settings->get_category();

        //if it is a reimport
        if($data = $settings->get_data()){
            if($data->qtype == TRUEFALSE){
                return new self($category);
            }else{
                return null;
            }
        }

        if(count($item->list_interactions())>1 || !self::has_score($item)){
            return null;
        }
        $main = self::get_main_interaction($item);
        if(!$main->is_choiceInteraction()){
            return null;
        }
        $choices = $main->list_simpleChoice();
        if(count($choices)!=2){
            return null;
        }
        $t0 = strtolower($choices[0]->value());
        $t1 = strtolower($choices[1]->value());
        $true = strtolower(get_string('true', 'qtype_truefalse'));
        $false = strtolower(get_string('false', 'qtype_truefalse'));
        if(($t0 == $true || $t0 == $false) && ($t1 == $true || $t1 == $false)){
            return new self($category);
        }else{
            return null;
        }
    }

    public function create_question(){
        $result = parent::create_question();
        $result->qtype = TRUEFALSE;
        $result->fraction = array();
        $result->answer = array();
        $result->feedbacktrue = array('text'=>'', 'format'=>FORMAT_HTML, 'itemid'=>null);
        $result->feedbackfalse = array('text'=>'', 'format'=>FORMAT_HTML, 'itemid'=>null);
        $result->questiontext='';
        $result->correctanswer=true;
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
        $result->penalty = $this->get_penalty($item);
        $general_feedbacks = $this->get_general_feedbacks($item);
        $result->generalfeedback = implode('<br/>', $general_feedbacks);
        $result->questiontext =$this->get_question_text($item);

        $interaction = self::get_main_interaction($item);
        $result->defaultgrade = $this->get_maximum_score($item, $interaction);

        $true = strtolower(get_string('true', 'qtype_truefalse'));
        $choices = $interaction->all_simpleChoice();
        foreach($choices as $choice){
            $feedback = $this->get_feedback($item, $interaction, $choice->identifier, $general_feedbacks);
            $score = $this->get_score($item, $interaction, $choice->identifier);
            $true_answer = strtolower($choice->value()) === $true;
            $result->correctanswer = $score == 0 ? !$true_answer : $true_answer;
            if($true_answer){
                $result->feedbacktrue= $this->format_text($feedback);
            }else{
                $result->feedbackfalse = $this->format_text($feedback);
            }
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
        $true_answer = $data->options->answers[$data->options->trueanswer];
        $false_answer = $data->options->answers[$data->options->falseanswer];

        $result->feedbacktrue= $this->format_text($true_answer->feedback);
        $result->feedbackfalse = $this->format_text($false_answer->feedback);
        $result->correctanswer = intval($true_answer->fraction) == 1;

        return $result;
    }

}








