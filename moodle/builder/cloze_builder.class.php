<?php

/**
 *
 * Question builder for MULTIANSWER/Cloze questions.
 *
 * University of Geneva
 * @author laurent.opprecht@unige.ch
 *
 */
class ClozeBuilder extends QuestionBuilder{

    static function factory(QtiImportSettings $settings){
        if(!defined('MULTIANSWER')){
            return null;
        }

        $item = $settings->get_reader();
        $category = $settings->get_category();

        //if it is a reimport
        if($data = $settings->get_data()){
            if($data->qtype == MULTIANSWER){
                return new self($category);
            }else{
                return null;
            }
        }

        if($item->has_templateDeclaration() || !self::has_score($item)){
            return null;
        }else{
            $interactions = $item->list_interactions();
            foreach($interactions as $interaction){
                if(!($interaction->is_inlineChoiceInteraction() ||
                $interaction->is_choiceInteraction() ||
                $interaction->is_textEntryInteraction() || //$main->is_hottextInteraction()
                $interaction->is_gapMatchInteraction())){
                    return null;
                }

            }
            return new self($category);
        }
    }

    public function create_question(){
        $result = parent::create_question();
        $result->qtype = MULTIANSWER;
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

        $text = $this->to_cloze($item);
        $result = qtype_multianswer_extract_question($text);
        $result->resources = array();
        $result->questiontextformat = FORMAT_HTML;
        $general_feedbacks = $this->get_general_feedbacks($item);
        $result->generalfeedback = implode('<br/>', $general_feedbacks);
        $result->name = $item->get_title();
        $result->penalty = $this->get_penalty($item);
        return $result;
    }

    protected function to_cloze($item){
        $cloze_renderer = new ClozeRenderer($this->get_strategy(), $item);
        $result = $cloze_renderer->render($item->get_itemBody());
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
        $result = parent::build_moodle($settings);
        $data = $settings->get_data();

        $text = $data->questiontext;
        foreach($data->options->questions as $key=>$q){
            $text = str_replace('{#' . $key . '}', $q->questiontext, $text);
        }

        $result = qtype_multianswer_extract_question($text);
        $result->resources = array();
        $result->questiontextformat = FORMAT_HTML;
        $result->name = $data->name;
        $result->generalfeedback = $data->generalfeedback;
        $result->penalty = $data->penalty;
        return $result;
    }

}















