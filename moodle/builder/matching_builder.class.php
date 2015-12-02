<?php

/**
 * Question builder for MATCH questions.
 *
 * University of Geneva
 * @author laurent.opprecht@unige.ch
 *
 */
class MatchingBuilder extends QuestionBuilder{

    static function factory(QtiImportSettings $settings){
        if(!defined('MATCH')){
            return null;
        }

        $item = $settings->get_reader();
        $category = $settings->get_category();

        //if it is a reimport
        if($data = $settings->get_data()){
            if($data->qtype == MATCH){
                return new self($category);
            }else{
                return null;
            }
        }

        if(!self::has_score($item)){
            return null;
        }else{
            $count = count($item->list_interactions());
            $main = self::get_main_interaction($item);
            if($count == 1 && $main->is_associateInteraction() || $main->is_matchInteraction()){
                return new self($category);
            }else{
                return null;
            }
        }
    }

    public function create_question(){
        $result = parent::create_question();
        $result->qtype = MATCH;
        $result->subquestions = array();
        $result->subanswers = array();
        $result->generalfeedback = '';
        return $result;
    }

    public function get_choices(ImsQtiReader $reader){
        $result = array();
        $choices = $reader->query('.//def:simpleAssociableChoice');
        foreach($choices as $choice){
            $id = $choice->identifier;
            $text = $this->to_text($choice);
            $max = $choice->matchMax; // not handled by moodle
            $result[$id] = $this->create_choice($id, $text, $max);
        }
        return $result;
    }

    public function create_choice($id='', $text='', $max=''){
        $result = new stdClass();
        $result->id = $id;
        $result->max = $max; // not handled by moodle
        $result->text = $text;
        return $result;
    }

    public function empty_choice(){
        return $this->create_choice();
    }

    public function create_entry($question=null, $answer){
        if(empty($question)){
            $question = $this->empty_choice();
        }
        if(empty($answer)){
            $answer = $this->empty_choice();
        }
        $result = new stdClass();
        $result->question = $question;
        $result->answer = $answer;
        return $result;
    }

    public function get_entries(ImsQtiReader $item, ImsQtiReader $interaction){
        $result = array();
        $sets = $interaction->list_simpleMatchSet();
        if(count($sets)==0){//associateInteraction
            $questions =  $this->get_choices($interaction);
            $answers = array_merge($questions);
        }else if(count($sets)==1){//should not be the case
            $questions = $this->get_choices($sets[0]);
            $answers = array_merge($questions);
        }else{//matchInteraction
            $questions = $this->get_choices($sets[0]);
            $answers = $this->get_choices($sets[1]);
        }
        $done = array();
        $responses = $this->get_correct_responses($item, $interaction);
        foreach($responses as $response){
            $pair = explode(' ', $response);
            $question = isset($questions[$pair[0]]) ? $questions[$pair[0]] : null;
            $answer = isset($answers[$pair[1]]) ? $answers[$pair[1]] : null;
            if(!empty($question)){
                $result[] = $this->create_entry($question, $answer);
                $done[$pair[1]] = $pair[1];
            }
        }
        foreach($answers as $key=>$answer){
            if(!isset($done[$key])){
                $result[] = $this->create_entry(null, $answer);
            }
        }

        return $result;
    }

    public function is_directed($item, $interaction){
        $response = $this->get_response($item, $interaction);
        return strtolower($response->baseType) == strtolower('directedPair');
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
        $result->generalfeedback = implode('<br/>', $general_feedbacks);

        $interaction = self::get_main_interaction($item);

        //$interaction->maxAssociations not handled by moodle
        $result->shuffleanswers = $interaction->shuffle == 'true' || $interaction->shuffle == '';

        $max = $this->get_maximum_score($item, $interaction);
        $result->defaultgrade = $max;

        $entries = $this->get_entries($item, $interaction);
        foreach($entries as $entry){
            $result->subquestions[]  = $this->format_text($entry->question->text);
            $result->subanswers[] = $entry->answer->text;
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

        $result->shuffleanswers = $data->options->shuffleanswers;
        $result->subquestions = array();
        $result->subanswers = array();

        foreach($data->options->subquestions as $q){
            $result->subquestions[]  = $this->format_text($q->questiontext);
            $result->subanswers[] = $q->answertext;
        }
        return $result;
    }

}








