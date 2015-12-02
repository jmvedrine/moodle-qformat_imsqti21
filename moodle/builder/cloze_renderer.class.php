<?php

/**
 * Render a QTI assessmentItem into moodle's MULTIANSWER/Cloze format.
 * University of Geneva
 * @author laurent.opprecht@unige.ch
 *
 */
class ClozeRenderer extends QtiRendererBase{

    /**
     *
     * @var QtiImportStrategy
     */
    private $strategy = null;
    private $max_score = 0;
    private $choices = array();
    private $interaction = null;
    private $assessment = null;

    public function __construct(QtiImportStrategyBase $strategy, $assessment){
        parent::__construct($strategy->get_renderer()->get_resource_manager());
        $this->strategy = $strategy;
        $this->assessment = $assessment;
    }

    protected function get_strategy(){
        return $this->strategy;
    }

    protected function get_assessment(){
        return $this->assessment;
    }

    protected function get_interaction(){
        return $this->interaction;
    }

    protected function get_max_score(){
        return $this->max_score;
    }

    protected function get_choices(){
        return $this->choices;
    }

    protected function create_map(){
        $result = parent::create_map();
        $result['gapMatchInteraction'] = 'span';
        $result['inlineChoiceInteraction'] = 'span';
        $result['prompt'] = 'div';
        $result['itemBody'] = 'span';
        return $result;
    }

    protected function process_inlineChoiceInteraction(ImsXmlReader $item, $prefix = '', $deep = true){
        $this->max_score = $max_score = $this->strategy->get_maximum_score($this->get_assessment(), $item);
        $rows = array();
        $children = $item->children();
        foreach($children as $child){
            if($copy = $this->process($child, $prefix, $deep, $item, $max_score)){
                $rows[] = $copy->nodeValue;
            }
        }
        $result = $this->question($max_score, 'MULTICHOICE', $rows);
        return $this->text($result);
    }

    protected function process_inlineChoice(ImsXmlReader $item, $prefix, $deep, $interaction, $max_score){
        $max_score = $this->max_score;
        $id = $item->identifier;
        $text = $this->strategy->to_text($item);
        $feedback = $this->get_feedback($interaction, $id);
        $score = $this->strategy->get_score($this->get_assessment(), $interaction, $id);
        return $this->text($this->answer($score/$max_score, $text, $feedback));
    }

    protected function process_choiceInteraction(ImsXmlReader $item, $prefix = '', $deep = true){
        $this->max_score = $max_score = $this->strategy->get_maximum_score($this->get_assessment(), $item);
        $rows = array();
        $children = $item->children();
        foreach($children as $child){
            if($copy = $this->process($child, $prefix, $deep, $item, $max_score)){
                $rows[] = $copy->nodeValue;
            }
        }
        $result = $this->question($max_score, 'MULTICHOICE', $rows);
        return $this->text($result);
    }

    protected function process_simpleChoice(ImsXmlReader $item, $prefix, $deep, $interaction, $max_score){
        $max_score = $this->max_score;
        $id = $item->identifier;
        $text = $this->strategy->to_text($item);
        $feedback = $this->get_feedback($interaction, $id);
        $score = $this->strategy->get_score($this->get_assessment(), $interaction, $id);
        return $this->text($this->answer($score/$max_score, $text, $feedback));
    }

    protected function process_textEntryInteraction(ImsXmlReader $item, $prefix = '', $deep = true){
        $max_score = $this->strategy->get_maximum_score($this->get_assessment(), $item);
        $this->max_score = $max_score = empty($max_score) ? 1 : $max_score;
        $answers = $this->strategy->get_possible_responses($this->get_assessment(), $item);
        $rows = array();
        foreach($answers as $answer){
            $response = $this->strategy->get_response_text($item, $answer);
            $feedback = $this->get_feedback($item, $answer);
            $score = $this->strategy->get_score($this->get_assessment(), $item, $answer);
            $entry = $this->answer($score/$max_score, $response, $feedback);
            $rows[] = $entry;
        }

        $incorrect = '*';
        $feedback = $this->get_feedback($item, $incorrect);
        $score = $this->strategy->get_score($this->get_assessment(), $item, $incorrect);
        $rows[] = $this->answer($score/$max_score, $incorrect, $feedback);

        $result = $this->question($max_score, 'SHORTANSWER_C', $rows);
        return $this->text($result);
    }

    protected function process_gapMatchInteraction(ImsXmlReader $item, $prefix = '', $deep = true){
        $result = $this->get_doc()->createElement('span');

        $max_score = $this->strategy->get_maximum_score($this->get_assessment(), $item);
        $choices = $item->list_gapText();
        $rows = array();
        foreach($choices as $choice){
            $id = $choice->identifier;
            $text = $choice->value();
            $rows[$id] = $text;
        }
        $this->interaction = $item;
        $this->max_score = $max_score;
        $this->choices = $rows;
        $children = $item->children();
        foreach($children as $child){
            if($child_copy = $this->process($child, $prefix, $deep, $item)){
                $result->appendChild($child_copy);
            }
        }

        $this->interaction = null;
        $this->max_score = 0;
        $this->choices = array();

        return $result;
    }

    protected function process_gap(ImsXmlReader $item, $prefix = '', $deep = true){
        $max_score = $this->max_score;
        $interaction = $this->get_interaction();
        $choices = $this->get_choices();
        $id = $item->identifier;
        $results = array();
        foreach($choices as $key=>$text){
            $answer = "$key $id";
            $result = new stdClass();
            $result->feedback = $this->get_feedback($interaction, $answer);
            $result->score = $this->strategy->get_score($this->get_assessment(), $interaction, $answer);
            $result->text = $text;
            $results[] = $result;
            $max_score = $max_score < $result->score ? $result->score : $max_score;
        }
        $answers = array();
        foreach($results as $result){
            $entry = $this->answer($result->score/$max_score, $result->text, $result->feedback);
            $answers[] = $entry;
        }
        return $this->text($this->question($max_score, 'MULTICHOICE', $answers));
    }

    protected function text($text){
        return $this->get_doc()->createTextNode($text);
    }

    protected function answer($percent, $text, $feedback){
        $feedback = $this->html_to_text($feedback);
        $text = $this->html_to_text($text);
        $percent = MoodleUtil::round_to_nearest_grade($percent);
        $percent = $percent*100;
        $result = "%$percent%$text#$feedback";
        return $result;
    }

    protected function question($score, $type, array $answers){
        $result = array();
        foreach($answers as $a){
            $result[] = $this->html_to_text($a);
        }
        $answers = $result;
        $result = implode('~', $answers);
        return '{'."$score:$type:$result" .'}';
    }

    protected function get_feedback($interaction, $answer){
        if(empty($this->general_feedbacks)){
            $this->general_feedbacks = $this->strategy->get_general_feedbacks($this->get_assessment());
        }
        $result = implode('<br/>', $this->strategy->get_feedbacks($this->get_assessment(), $interaction, $answer, $this->general_feedbacks));
        $result = html_trim_tag($result, 'span', 'p');
        return $result;
    }

    protected function html_to_text($text){
        return $text;
    }
}












