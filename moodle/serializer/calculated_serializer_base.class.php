<?php

//require_once dirname(dirname(__FILE__)) .'/main.php';

/**
 * Serializer base class for calculated questions.
 *
 * University of Geneva
 * @author laurent.opprecht@unige.ch
 *
 */
class CalculatedSerializerBase extends NumericalSerializerBase{

    public function __construct($target_root){
        parent::__construct($target_root);
    }

    protected function add_response_declaration(ImsQtiWriter $item, $question){
        //main response
        $cardinality =  ImsQtiWriter::CARDINALITY_SINGLE;
        $base_type = ImsQtiWriter::BASETYPE_FLOAT;
        $result = $item->add_responseDeclaration(ImsQtiWriter::RESPONSE, $cardinality, $base_type);

        $this->add_unit_declaration($item, $question);
        return $result;
    }

    protected function add_template_declaration(ImsQtiWriter $item, $question){
        $result = null;
        $datasets = isset($question->options->datasets) ? $question->options->datasets : array();
        foreach($datasets as $dataset){
            $id = $dataset->name;
            $cardinality = ImsQtiWriter::CARDINALITY_SINGLE;
            $basetype = ImsQtiWriter::BASETYPE_FLOAT;
            $result = $item->add_templateDeclaration($id, $cardinality, $basetype, false, false);
        }
        return $result;
    }

    protected function add_template_processing(ImsQtiWriter $item, $question){
        $result = $item->add_templateProcessing();
        $datasets = isset($question->options->datasets) ? $question->options->datasets : array();
        foreach($datasets as $dataset){
            $items = $dataset->items;
            if(!empty($items)){
                $id = $dataset->name;
                $multiple = $result->add_setTemplateValue($id)->add_random()->add_multiple();
                foreach($items as $item){
                    $basetype = ImsQtiWriter::BASETYPE_FLOAT;
                    $multiple->add_baseValue($basetype, $item->value);
                }
            }
        }
        return $result;
    }

    protected function add_score_processing(ImsQtiWriter $response_processing, $question){
        $this->add_unit_processing($response_processing, $question);

        $result = $response_processing->add_responseCondition();
        $response_id = ImsQtiWriter::RESPONSE;
        $outcome_id = ImsQtiWriter::SCORE;
        $if = $result->add_responseIf();
        $if->add_isNull()->add_variable($response_id);
        $if->add_setOutcomeValue($outcome_id)->add_baseValue(ImsQtiWriter::BASETYPE_FLOAT, 0);
        foreach($question->options->answers as $answer){
                $formula_serializer = new CalculatedFormulaSerializer();
                if($formula = $formula_serializer->parse($answer->answer)){
                    $score = $answer->fraction * $question->defaultgrade;
                    //tolerance mode geometric not supported by QTI
                    $tolerance_mode = $answer->tolerancetype == 1 ? ImsQtiWriter::TOLERANCE_MODE_RELATIVE : ImsQtiWriter::TOLERANCE_MODE_ABSOLUTE;
                    $elseif = $result->add_responseElseIf();
                    $equal = $elseif->add_equal($tolerance_mode, $answer->tolerance, $answer->tolerance);
                    $equal->add_flow($formula);
                    $product = $equal->add_product();
                    $product->add_variable($response_id);
                    $product->add_variable(self::UNIT_MULTIPLIER);
                    $elseif->add_setOutcomeValue($outcome_id)->add_baseValue(ImsQtiWriter::BASETYPE_FLOAT, $score);
                }else{
                    //TODO:report error
                }
        }
        $else = $result->add_responseElse();
        $else->add_setOutcomeValue($outcome_id)->add_baseValue(ImsQtiWriter::BASETYPE_FLOAT, 0);
        return $result;
    }

    protected function add_answer_feedback_processing(ImsQtiWriter $response_processing, $question){
        $result = $response_processing->add_responseCondition();
        $response_id = ImsQtiWriter::RESPONSE;
        $outcome_id = ImsQtiWriter::FEEDBACK;
        $if = $result->add_responseIf();
        $if->add_isNull()->add_variable($response_id);
        $if->add_setOutcomeValue($outcome_id)->add_baseValue(ImsQtiWriter::BASETYPE_IDENTIFIER, 'FEEDBACK_ID_ELSE');
        $count = 0;
        foreach($question->options->answers as $answer){
            $id = 'FEEDBACK_ID_' . ++$count;
            $formula_serializer = new CalculatedFormulaSerializer();
            if($formula = $formula_serializer->parse($answer->answer)){
                //tolerance mode geometric not supported by QTI
                $tolerance_mode = $answer->tolerancetype == 1 ? ImsQtiWriter::TOLERANCE_MODE_RELATIVE : ImsQtiWriter::TOLERANCE_MODE_ABSOLUTE;
                $elseif = $result->add_responseElseIf();
                $equal = $elseif->add_equal($tolerance_mode, $answer->tolerance, $answer->tolerance);
                $equal->add_flow($formula);
                $product = $equal->add_product();
                $product->add_variable($response_id);
                $product->add_variable(self::UNIT_MULTIPLIER);

                $elseif->add_setOutcomeValue($outcome_id)->add_baseValue(ImsQtiWriter::BASETYPE_IDENTIFIER, $id);
            }
        }
        $else = $result->add_responseElse();
        $else->add_setOutcomeValue($outcome_id)->add_baseValue(ImsQtiWriter::BASETYPE_IDENTIFIER, 'FEEDBACK_ID_ELSE');

        return $result;
    }

    protected function add_interaction(ImsQtiWriter $body, $question){
        if($question->options->unitsleft){
            $this->add_unit($body, $question);
            $result = $body->add_extendedTextInteraction(Qti::RESPONSE, '', 1, 1);
        }else{
            $result = $body->add_extendedTextInteraction(Qti::RESPONSE, '', 1, 1);
            $this->add_unit($body, $question);
        }
        $instructions = $question->options->instructions;
        if(!empty($instructions)){
            $body->add_rubricBlock(ImsQtiWriter::VIEW_ALL)->add_flow($instructions);
        }
        return $result;
    }

    protected function translate_question_text($text, $text_format=self::FORMAT_HTML, $question=null){
        $result = parent::translate_question_text($text, $text_format, $question);
        $pattern = "/^\{[a-zA-Z_][a-zA-Z0-9_]*\}/";;
        $datasets = isset($question->options->datasets) ? $question->options->datasets : array();
        foreach($datasets as $dataset){
            $name = '{'. $dataset->name .'}';
            $replace = '<printedVariable identifier="'.$dataset->name.'" />';
            $result = str_replace($name, $replace, $result);
        }
        return $result;

    }
}














