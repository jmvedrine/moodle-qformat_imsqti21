<?php

/**
 * Question builder for description questions.
 *
 * University of Geneva
 * @author laurent.opprecht@unige.ch
 *
 */
class DescriptionBuilder extends QuestionBuilder{

    static function factory(QtiImportSettings $settings){

        $item = $settings->get_reader();
        $category = $settings->get_category();

        //if it is a reimport
        if($data = $settings->get_data()){
            if($data->qtype == 'description'){
                return new self($category);
            }else{
                return null;
            }
        }

        $count = count($item->list_interactions());
        if($count == 0 ){
            return new self($category);
        }else{
            return null;
        }
    }

    public function create_question(){
        $result = parent::create_question();
        $result->qtype = 'description';
        $result->fraction = 0;
        $result->defaultgrade = 0;
        $result->feedback = '';
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
        return $result;
    }

}








