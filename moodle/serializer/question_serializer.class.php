<?php

//require_once dirname(dirname(__FILE__)) .'/main.php';

/**
 * Base class for primary question serializers. I.e. serializers for moodle's questions' types
 *
 * The parent QuestionSerializerBase class is used by subquestions serializers as well.
 *
 * University of Geneva
 * @author laurent.opprecht@unige.ch
 *
 */
class QuestionSerializer extends QuestionSerializerBase{

    const MOODLE_QUESTION_DATA = 'MOODLE_QUESTION_DATA';

    static function question_identifier($question){
        $catalog_name = MoodleUtil::get_catalog_name();
        $result = "$catalog_name:Q_". str_pad($question->id, 8, '0', STR_PAD_LEFT);
        return $result;
    }

    /**
     * @return QuestionSerializer
     */
    static function factory($question, $target_root){
        /*
         $directory = dirname(__FILE__);
         $entries = scandir($directory);
         foreach($entries as $entry){
            $path = $directory. '/'. $entry;
            if(self::accept_file($path)){
            $path = realpath($path);
            require_once $path;
            $class = self::class_name($path);
            if($result = $class::factory($question, $source_root, $target_root)){
            return $result;
            }
            }
            }
            */

        if($result = EssaySerializer::factory($question, $target_root)){
            return $result;
        }else if($result = DescriptionSerializer::factory($question, $target_root)){
            return $result;
        }else if($result = TrueFalseSerializer::factory($question, $target_root)){
            return $result;
        }else if($result = MultiplechoiceSerializer::factory($question, $target_root)){
            return $result;
        }else if($result = MatchingSerializer::factory($question, $target_root)){
            return $result;
        }else if($result = ShortanswerSerializer::factory($question, $target_root)){
            return $result;
        }else if($result = NumericalSerializer::factory($question, $target_root)){
            return $result;
        }else if($result = CalculatedSerializer::factory($question, $target_root)){
            return $result;
        }else if($result = CalculatedSimpleSerializer::factory($question, $target_root)){
            return $result;
        }else if($result = CalculatedMultiSerializer::factory($question, $target_root)){
            return $result;
        }else if($result = MultipleAnswerSerializer::factory($question, $target_root)){
            return $result;
        }else{
            return null;
        }
    }

    /**
     * @return SubquestionSerializer
     */
    static function factory_subquestion($question, $resource_manager){
        $result = EssaySerializer::factory_subquestion($question, $resource_manager);
        if(! $result instanceof SubquestionSerializerEmpty){
            return $result;
        }
        $result = DescriptionSerializer::factory_subquestion($question, $resource_manager);
        if(! $result instanceof SubquestionSerializerEmpty){
            return $result;
        }
        $result = TrueFalseSerializer::factory_subquestion($question, $resource_manager);
        if(! $result instanceof SubquestionSerializerEmpty){
            return $result;
        }
        $result = MultiplechoiceSerializer::factory_subquestion($question, $resource_manager);
        if(! $result instanceof SubquestionSerializerEmpty){
            return $result;
        }
        $result = MatchingSerializer::factory_subquestion($question, $resource_manager);
        if(! $result instanceof SubquestionSerializerEmpty){
            return $result;
        }
        $result = ShortanswerSerializer::factory_subquestion($question, $resource_manager);
        if(! $result instanceof SubquestionSerializerEmpty){
            return $result;
        }
        $result = NumericalSerializer::factory_subquestion($question, $resource_manager);
        if(! $result instanceof SubquestionSerializerEmpty){
            return $result;
        }
        $result = CalculatedSerializer::factory_subquestion($question, $resource_manager);
        if(! $result instanceof SubquestionSerializerEmpty){
            return $result;
        }
        $result = CalculatedSimpleSerializer::factory_subquestion($question, $resource_manager);
        if(! $result instanceof SubquestionSerializerEmpty){
            return $result;
        }
        $result = CalculatedMultiSerializer::factory_subquestion($question, $resource_manager);
        if(! $result instanceof SubquestionSerializerEmpty){
            return $result;
        }
        $result = MultipleAnswerSerializer::factory_subquestion($question, $resource_manager);
        if(! $result instanceof SubquestionSerializerEmpty){
            return $result;
        }
        return new SubquestionSerializerEmpty();
    }

    protected $question = null;
    protected $assessment = null;
    protected $response = null;
    protected $score = null;
    protected $response_processing = null;
    protected $body = null;
    protected $interaction = null;

    public function __construct($target_root){
        parent::__construct($target_root);
    }

    protected function init($question){
        $this->question = $question;
        $this->assessment = null;
        $this->response = null;
        $this->score = null;
        $this->response_processing = null;
        $this->body = null;
        $this->interaction = null;
    }

    public function serialize($question){
        $this->init($question);
        $writer = new ImsQtiWriter();
        $this->assessment = $item = $this->add_assessment_item($writer, $question);
        $this->add_response_declaration($item, $question);
        $this->add_outcome_declaration($item, $question);
        $this->add_template_declaration($item, $question);
        $this->add_template_processing($item, $question);
        $this->add_stylesheet($item, $question);
        $this->body = $this->add_body($item, $question);
        $this->response_processing = $this->add_response_processing($item, $question);
        $this->add_modal_feedback($item, $question);
        $this->add_question_data($item, $question);
        $result = $writer->saveXML();

        return $result;
    }

    protected function add_assessment_item(ImsQtiWriter $writer, $question){
        $identifier = self::question_identifier($question);
        $lang = MoodleUtil::get_current_language();
        $label = $question->qtype;
        $toolname = self::get_tool_name();
        $toolversion = Qti::get_tool_version();
        $result = $writer->add_assessmentItem($identifier, $question->name, true, false, $label, $lang, $toolname, $toolversion);
        return $result;
    }

    protected function add_stylesheet(ImsQtiWriter $item, $question){
        return null;
    }

    protected function add_response_declaration(ImsQtiWriter $item, $question){
        return $this->response = $item->add_responseDeclaration(ImsQtiWriter::RESPONSE, ImsQtiWriter::CARDINALITY_SINGLE, ImsQtiWriter::BASETYPE_IDENTIFIER);
    }

    protected function add_outcome_declaration(ImsQtiWriter $item, $question){
        $this->score = $this->add_score_declaration($item, $question);
        $this->add_penalty_declaration($item, $question);
        $this->add_general_feedback_declaration($item, $question);
        $this->add_answer_feedback_declaration($item, $question);
    }

    //TEMPLATE

    protected function add_template_processing(ImsQtiWriter $item, $question){
        return null;
    }

    protected function add_template_declaration(ImsQtiWriter $item, $question){
        return null;
    }

    //BODY

    protected function add_body(ImsQtiWriter $item, $question){
        $result = $item->add_itemBody();
        $text = $this->translate_question_text($question->questiontext, $question->questiontextformat, $question);
        $result->add_flow($text);
        $this->interaction = $this->add_interaction($result, $question);
        return $result;
    }

    protected function add_interaction(ImsQtiWriter $body, $question){
        return null;
    }

    //FEEDBACK

    protected function add_modal_feedback(ImsQtiWriter $item, $question){
        $this->add_general_feedback($item, $question);
        $this->add_answer_feedback($item, $question);
    }

    protected function add_general_feedback_declaration(ImsQtiWriter $item, $question){
        if($has_feedback = !empty($question->generalfeedback)){
            $id = self::GENERAL_FEEDBACK;
            $value = 'true';
            $result = $item->add_outcomeDeclaration_feedback($id)->add_defaultValue()->add_value($value);
            return $result;
        }else{
            return null;
        }
    }

    protected function add_general_feedback(ImsQtiWriter $item, $question){
        if($has_feedback = !empty($question->generalfeedback)){
            $id = self::GENERAL_FEEDBACK;
            $value = 'true';
            $text = $this->translate_feedback_text($question->generalfeedback, self::FORMAT_HTML, $question);
            $result = $item->add_modalFeedback($id, $value, 'show')->add_flow($text);
            return $result;
        }else{
            return null;
        }
    }

    protected function add_answer_feedback_declaration(ImsQtiWriter $item, $question){
        if($this->has_answer_feedback($question)){
            $result = $item->add_outcomeDeclaration_feedback();
            $result->add_defaultValue()->add_value('DEFAULT_FEEDBACK');
            return $result;
        }else{
            return null;
        }
    }

    protected function add_answer_feedback(ImsQtiWriter $item, $question){
        return null;
    }

    protected function add_answer_feedback_processing(ImsQtiWriter $processing, $question){
        if($this->has_answer_feedback($question)){
            $result = $processing->add_standard_response_assign_feedback();
            return $result;
        }else{
            return null;
        }
    }

    protected function has_answer_feedback($question){
        if(!isset($question->options) || !isset($question->options->answers) || !is_array($question->options->answers)){
            return false;
        }
        foreach($question->options->answers as $answer){
            if(isset($answer->feedback) && !empty($answer->feedback)){
                return true;
            }
        }
        return false;
    }

    // SCORE

    protected function add_score_declaration(ImsQtiWriter $item, $question){
        $score = $question->defaultgrade;
        $cardinality = ImsQtiWriter::CARDINALITY_SINGLE;
        $name = ImsQtiWriter::SCORE;
        $base_type = is_int($score) ? ImsQtiWriter::BASETYPE_INTEGER : ImsQtiWriter::BASETYPE_FLOAT;
        $result = $item->add_outcomeDeclaration($name, $cardinality, $base_type, $score);
        $result->add_defaultValue()->add_value(0);
        return $result;
    }

    protected function add_score_processing(ImsQtiWriter $response_processing, $question){
        return null;
    }

    // PENALTY

    protected function has_penalty($question){
        return !empty($question->penalty);
    }

    protected function add_penalty_declaration(ImsQtiWriter $item, $question){
        if(!$this->has_penalty($question)){
            return null;
        }

        $cardinality = ImsQtiWriter::CARDINALITY_SINGLE;
        $name = self::PENALTY;
        $base_type = ImsQtiWriter::BASETYPE_FLOAT ;
        $score_outcome = $item->add_outcomeDeclaration($name, $cardinality, $base_type);
        $score_outcome->add_defaultValue()->add_value(0);

        return $score_outcome;
    }

    protected function add_penalty_increase(ImsQtiWriter $processing, $question, $penalty_id = self::PENALTY){
        if(!$this->has_penalty($question)){
            return null;
        }
        $penalty_value = $question->penalty;
        $result = $processing->add_responseCondition();
        $if = $result->add_responseIf();
        $if->add_isNull()->add_variable($penalty_id);
        $if->add_setOutcomeValue($penalty_id)->add_baseValue(ImsQtiWriter::BASETYPE_FLOAT, $penalty_value);
        $sum = $result->add_responseElse()->add_setOutcomeValue($penalty_id)->add_sum();
        $sum->add_baseValue(ImsQtiWriter::BASETYPE_FLOAT, $penalty_value);
        $sum->add_variable($penalty_id);
        return $result;
    }

    protected function add_add_penalty(ImsQtiWriter $processing, $question, $input_id = ImsQtiWriter::SCORE,  $score_id = ImsQtiWriter::SCORE, $penalty_id = self::PENALTY){
        if(!$this->has_penalty($question)){
            return null;
        }

        $result = $processing->add_setOutcomeValue($score_id);
        $sum = $result->add_subtract();
        $sum->add_variable($input_id);
        $sum->add_variable($penalty_id);

        $result = $processing->add_responseCondition();
        $if = $result->add_responseIf();
        $lt = $if->add_lt();
        $lt->add_variable($input_id);
        $lt->add_baseValue(ImsQtiWriter::BASETYPE_FLOAT, 0);
        $if->add_setOutcomeValue($input_id)->add_baseValue(ImsQtiWriter::BASETYPE_FLOAT, 0);
        return $result;
    }

    protected function add_response_processing(ImsQtiWriter $item, $question){
        $result = $item->add_responseProcessing();
        $this->add_score_processing($result, $question);
        $this->add_add_penalty($result, $question);
        $this->add_penalty_increase($result, $question); //last processing to perform;
        $this->add_answer_feedback_processing($result, $question);
        return $result;
    }

    /**
     * Serialize additional information not or not well supported by QTI.
     * Used for reimport.
     */
    protected function add_question_data(ImsQtiWriter $item, $question){
        $id = self::MOODLE_QUESTION_DATA;
        $value = 'true';

        $q = clone $question;
        $q = $this->get_question_data($q);

        $text = serialize($q);
        $result = $item->add_modalFeedback($id, $value, 'show')->add_text($text);
        return $result;
    }

    /**
     * Returns data to be serialized on top of the QTI format.
     * Made of the question's object minus fields that don't have a meaning in another system.
     *
     * Remove question's fields which don't have a meaning in another system.
     * For example question id, user id, etc
     *
     * @param object $question
     * @return object
     */
    protected function get_question_data($question){
        //does not have a meaning in another system - ids, user ids, etc
        unset($question->id);
        unset($question->category);
        unset($question->parent);
        unset($question->createdby);
        unset($question->modifiedby);
        unset($question->stamp);
        unset($question->version);

        if(isset($question->options)){
            unset($question->options->question);
            if(isset($question->options->subquestions)){
                foreach($question->options->subquestions as $q){
                    unset($q->question);
                }
            }
        }

        /**
         * note: @@PLUGINFILE@@ have to be replaced ->before<- the object is serialized.
         * Doing it after - i.e. after the xml file has been generated - will fail because it changes the length of string
         */
        $question->questiontext = str_replace('@@PLUGINFILE@@', 'resources', $question->questiontext);

        if(isset($question->generalfeedback)){
            $question->generalfeedback = str_replace('@@PLUGINFILE@@', 'resources', $question->generalfeedback);
        }
        if(isset($question->options)){
            if(isset($question->options->correctfeedback)){
                $question->correctfeedback = str_replace('@@PLUGINFILE@@', 'resources', $question->options->correctfeedback);
            }
            if(isset($question->options->partiallycorrectfeedback)){
                $question->partiallycorrectfeedback = str_replace('@@PLUGINFILE@@', 'resources', $question->options->partiallycorrectfeedback);
            }
            if(isset($question->options->incorrectfeedback)){
                $question->incorrectfeedback = str_replace('@@PLUGINFILE@@', 'resources', $question->options->incorrectfeedback);
            }
        }

        return $question;
    }

}















