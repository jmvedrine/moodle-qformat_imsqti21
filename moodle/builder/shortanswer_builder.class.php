<?php

/**
 * Question builder for shortanswer questions.
 *
 * University of Geneva
 * @author laurent.opprecht@unige.ch
 *
 */
class ShortanswerBuilder extends QuestionBuilder{

    static function factory(QtiImportSettings $settings){

        $item = $settings->get_reader();
        $category = $settings->get_category();

        //if it is a reimport
        if($data = $settings->get_data()){
            if($data->qtype == 'shortanswer'){
                return new self($category);
            }else{
                return null;
            }
        }

        if(count($item->list_interactions())!=1 || !self::has_score($item)){
            return null;
        }else{
            $main = self::get_main_interaction($item);
            $is_text_entry = $main->is_extendedTextInteraction() || $main->is_textEntryInteraction();
            $is_numeric = self::is_numeric_interaction($item, $main);
            $has_answers = self::has_answers($item, $main);
            if($is_text_entry && !$is_numeric && $has_answers ){
                return new self($category);
            }else{
                return null;
            }
        }
    }

    public function create_question(){
        $result = parent::create_question();
        $result->qtype = 'shortanswer';
        $result->fraction = array();
        $result->answer = array();
        $result->feedback = array();
        $question->usecase = false;
        return $result;
    }

    protected function get_response_text($item, $response){
        if(! $response instanceof ImsXmlReader){
            $result = $response;
        }else if($response->is_patternMatch()){
            $result = $this->translate_regex($response->pattern);

        }else{
            $result = $this->execute_formula($item, $response);
        }

        return $result;
    }

    /**
     * Very simple translation. Mostly intended to reimport the export.
     * @param unknown_type $regex
     */
    public function translate_regex($regex){
        $result = $regex;
        $letters = 'a b c d e f g h i j k l m n o p q r s t u v w x y z';
        $letters = explode(' ', $letters);
        foreach($letters as $letter){
            $pattern = '['. strtolower($letter).strtoupper($letter) . ']';
            $result = str_ireplace($pattern, $letter, $result);
        }
        $quantifiers = '+ * ?';
        $quantifiers = explode(' ', $quantifiers);
        foreach($quantifiers as $quantifier){
            $pattern = ".$quantifier";
            $result = str_replace($pattern, '*', $result);
        }
        $pattern = '/\\.{\\d+, \\d+}/';
        $result = preg_replace($pattern, '*', $result);
        $result = $this->preg_unquote($result);
        return $result;
    }

    public function preg_unquote($text){
        $result = $text;
        $chars = '. \\ + * ? [ ^ ] $ ( ) { } = ! < > | : -';
        $chars = explode(' ', $chars);
        foreach($chars as $char){
            $pattern = '\\'.$char;
            $result = str_replace($pattern, $char, $result);
        }
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
        $result->usecase = $this->is_case_sensitive($item);

        $general_feedbacks = $this->get_general_feedbacks($item);
        $result->generalfeedback = implode('<br/>', $general_feedbacks);

        $result->defaultgrade = $this->get_maximum_score($item);

        $interaction = self::get_main_interaction($item);
        $responses = $this->get_possible_responses($item, $interaction);
        foreach($responses as $response){
            $result->answer[] = $this->get_response_text($item, $response);
            $result->feedback[] = $this->format_text($this->get_feedback($item, $interaction, $response, $general_feedbacks));
            $result->fraction[] = $this->get_fraction($item, $interaction, $response, $result->defaultgrade);
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

        $result->usecase = $data->options->usecase;
        foreach($data->options->answers as $a){
            $result->answer[] = $a->answer;
            $result->feedback[] = $this->format_text($a->feedback);
            $result->fraction[] = $a->fraction;

        }
        return $result;

    }
}



















