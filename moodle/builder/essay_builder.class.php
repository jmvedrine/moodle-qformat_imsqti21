<?php

/**
 * Question builder for essay questions.
 *
 * University of Geneva
 * @author laurent.opprecht@unige.ch
 *
 */
class EssayBuilder extends QuestionBuilder{

    static function factory(QtiImportSettings $settings){

        $item = $settings->get_reader();
        $category = $settings->get_category();

        //if it is a reimport
        if($data = $settings->get_data()){
            if($data->qtype == 'essay'){
                return new self($category);
            }else{
                return null;
            }
        }
        $count = count($item->list_interactions());
        $main = self::get_main_interaction($item);
        $has_answers = self::has_answers($item, $main);
        if($count == 1 && $main->is_extendedTextInteraction() && !$has_answers){
            return new self($category);
        }else{
            return null;
        }
    }

    public function create_question(){
        $result = parent::create_question();
        $result->qtype = 'essay';
        $result->fraction = 0; //essays have no score untill graded by the teacher.
        $result->feedback = $this->format_text('');
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
        $result->defaultgrade = $this->get_maximum_score($item);
        $general_feedbacks = $this->get_general_feedbacks($item);
        $result->generalfeedback = implode('<br/>', $general_feedbacks);
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
        $a = reset($data->options->answers);
        $result->feedback = $this->format_text($a->feedback);
        return $result;
    }

}








