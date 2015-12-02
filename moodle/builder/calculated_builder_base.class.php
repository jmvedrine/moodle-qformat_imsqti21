<?php

/**
 * Base class for calculated builders.
 *
 * University of Geneva
 * @author laurent.opprecht@unige.ch
 *
 */
class CalculatedBuilderBase extends NumericalBuilderBase{

    protected function get_answers(ImsXmlReader $item, $interaction = null){
        $result = array();
        $interaction = empty($interaction) ? $this->get_main_interaction($item): $interaction;
        $formulas = $this->get_strategy()->get_score_formulas($item, $interaction);
        foreach($formulas as $formula){
            $result[] = $this->render_formula($formula);
        }
        return $result;
    }

    protected function render_formula(ImsXmlReader $expression){
        $renderer = new CalculatedFormulaBuilder();
        return $renderer->render($expression);
    }

    protected function get_question_text(ImsXmlReader $item){
        $body = $item->get_itemBody();
        return $this->to_html($body);;
    }

    protected function to_text(ImsXmlReader $item){
        $xml = $item->get_xml();
        $variables = $item->all_printedVariable();
        foreach($variables as $var){
            $var_xml = $var->get_xml();
            $name = '{'. $var->identifier .'}';
            $xml = str_replace($var_xml, $name, $xml);
        }
        $xml = '<?xml version="1.0" encoding="UTF-8" ?>' . $xml;
        $item = new ImsQtiReader();
        $item->load_xml($xml);
        $result = parent::to_text($item);
        return $result;
    }

    protected function to_html(ImsXmlReader $item){
        $xml = $item->get_xml();
        $variables = $item->all_printedVariable();
        foreach($variables as $var){
            $var_xml = $var->get_xml();
            $name = '{'. $var->identifier .'}';
            $xml = str_replace($var_xml, $name, $xml);
        }
        $xml = '<?xml version="1.0" encoding="UTF-8" ?>' . $xml;
        $item = new ImsQtiReader();
        $item->load_xml($xml);
        $result = parent::to_html($item);
        return $result;
    }

    protected function get_datasets(ImsQtiReader $item){
        $result = array();
        $templates = $this->get_template_values($item);
        $count = 1;
        foreach($templates as $name => $values){
            $result[$count] = $this->create_dataset($name, $values);
            $count++;
        }
        return $result;
    }

    protected function create_dataset($name, $values){
        $result = new stdClass();
        $result->name = $name;
        $result->distribution = 'uniform';
        $result->type = 'calculated';
        $result->min = min($values);
        $result->max = max($values);
        $result->length = 1;
        $result->itemcount = count($values);
        $result->status = 'private';
        $result->datasetitem = array();
        $count = 1;
        //$result->datasetindex = 0;
        foreach($values as $value){
            $result->datasetindex++;
            $item = new stdClass;
            //$item->id ;
            $item->itemnumber = $count;
            $item->value = $value;
            $result->datasetitem[$count] = $item;
            $count++;
        }
        return $result;
    }

    protected function get_correctanswerlength(ImsXmlReader $item, $interaction, $answer){
        return 2; //show to decimals
    }

    protected function get_tolerancetype(ImsXmlReader $item, $interaction, $answer){
        return self::TOLERANCE_TYPE_RELATIVE;
    }

    protected function get_correctanswerformat(ImsXmlReader $item, $interaction, $answer){
        return self::ANSWER_FORMAT_DECIMAL;
    }

}