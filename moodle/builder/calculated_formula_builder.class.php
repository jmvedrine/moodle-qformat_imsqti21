<?php

/**
 * Transforms an IMS QTI expression into a moodle's expression used by calculated questions.
 * I.e. a PHP expression where variables's name have the {variable_name} format.
 *
 * University of Geneva
 * @author laurent.opprecht@unige.ch
 *
 */
class CalculatedFormulaBuilder{

    private $break = false;

    public function render(ImsQtiReader $expression){
        $this->break = false;
        $result = $this->process($expression);
        $result = $this->cleanup($result);
        return $result;
    }

    protected function cleanup($text){
        $result = $text;
        $result = trim($result);
        //$result = trim($result, '()');
        return $result;
    }

    protected function process(ImsXmlReader $item, $_ = ''){
        if($this->break) return;
        if($item->is_empty()) return;

        $f = array($this, 'process_'.$item->name());
        $args = func_get_args();
        if(is_callable($f)){
            return call_user_func_array($f, $args);
        }else{
            throw new Exception('Unknown: '.$item->name());
        }
    }

    protected function process_children(ImsXmlReader $item, $_ = ''){
        if($this->break) return;

        $result = false;
        $args = func_get_args();
        $children = $item->children();
        foreach($children as $child){
            $args[0] = $child;
            $result = call_user_func_array(array($this, 'process'), $args);
        }
        return $result;
    }

    protected function process_all(ImsXmlReader $items, $_=''){
        if($this->break) return;

        $result = false;
        $args = func_get_args();
        $f = array($this, 'process');
        foreach($items as $item){
            $args[0] = $item;
            $result = call_user_func_array($f, $args);
        }
        return $result;
    }

    protected function process_infix(ImsXmlReader $item, $op){
        $result = array();
        $children = $item->children();
        foreach($children as $child){
            $result[] = $this->process($child);
        }
        if(count($result) == 1){
            return $result;
        }else{
            return '('. implode($op, $result) .')';
        }

    }

    protected function process_sum(ImsXmlReader $item){
        return $this->process_infix($item, '+');
    }

    protected function process_subtract(ImsXmlReader $item){
        return $this->process_infix($item, '-');
    }

    protected function process_product(ImsXmlReader $item){
        return $this->process_infix($item, '*');
    }

    protected function process_divide(ImsXmlReader $item){
        return $this->process_infix($item, '/');
    }

    protected function process_integerDivide(ImsXmlReader $item){
        $result = $this->process_infix($item, '/');
        $result = "floor($result)";
        return $result;
    }

    protected function process_integerModulus(ImsXmlReader $item){
        return $this->process_infix($item, '%');
    }

    protected function process_power(ImsXmlReader $item){
        $children = $item->children();
        $base = $this->process($children[0]);
        $exp = $this->process($children[1]);
        $result =  "pow($base, $exp)";
        return $result;
    }

    protected function process_truncate(ImsXmlReader $item){
        $children = $item->children();
        $expression = $this->process($children[0]);
        $result =  "floor($expression)";
        return $result;
    }

    protected function process_round(ImsXmlReader $item){
        $children = $item->children();
        $expression = $this->process($children[0]);
        $result =  "round($expression)";
        return $result;
    }

    protected function process_integerToFloat(ImsXmlReader $item){
        $children = $item->children();
        $expression = $this->process($children[0]);
        return $expression;
    }

    protected function process_customOperator(ImsXmlReader $item){
        $name = $item->class;
        $children = $item->children();
        $args = array();
        foreach($children as $child){
            $args[] = $this->process($child);
        }
        $args = implode(',', $args) ;
        $result = "$name($args)";
        return $result;
    }

    protected function process_gte(ImsXmlReader $item){
        return $this->process_infix($item, '>=');
    }

    protected function process_lte(ImsXmlReader $item){
        return $this->process_infix($item, '<=');
    }

    protected function process_gt(ImsXmlReader $item){
        return $this->process_infix($item, '>');
    }

    protected function process_lt(ImsXmlReader $item){
        return $this->process_infix($item, '<');
    }

    protected function process_equalRounded(ImsXmlReader $item){
        $children = $item->children();
        $figures = $item->figures;
        $left = $this->process($children[0]);
        $right = $this->process($children[1]);
        $result = "(round($left, $figures)==round($right, $figures)";
        return $result;
    }

    protected function process_equal(ImsXmlReader $item){
        return $this->process_infix($item, '==');
    }

    protected function process_patternMatch(ImsXmlReader $item){
        $children = $item->children();
        $pattern = $item->pattern;
        $expression = $this->process($children[0]);
        $result = "preg_match($pattern, $expression)";
        return $result;
    }

    protected function process_stringMatch(ImsXmlReader $item){
        $children = $item->children();
        $substring = $item->substring;
        $case_sensitive = $item->caseSensitive;
        $left = $this->process($children[0]);
        $right = $this->process($children[1]);
        if($substring){
            if($case_sensitive){
                return "(strpos($left, $right) !== false)";
            }else{
                return "(strpos(strtolower($left), strtolower($right)) !== false)";
            }
        }else{
            if($case_sensitive){
                return "($left==$right)";
            }else{
                return "(strtolower($left)==strtolower($right))";
            }
        }
    }

    protected function process_match(ImsXmlReader $item){
        return $this->process_infix($item, '==');
    }

    protected function process_or(ImsXmlReader $item){
        return $this->process_infix($item, '||');
    }

    protected function process_and(ImsXmlReader $item){
        return $this->process_infix($item, '&&');
    }

    protected function process_not(ImsXmlReader $item){
        $children = $item->children();
        $expression = $this->process($children[0]);
        return "! $expression";
    }

    protected function process_isNull(ImsXmlReader $item){
        $children = $item->children();
        $expression = $this->process($children[0]);
        return "is_null($expression)";
    }

    protected function process_randomFloat(ImsXmlReader $item){
        $min = empty($item->min) ? 0 : $item->min;
        $max = $item->max;
        return "rand($min, $max)";
    }

    protected function process_randomInteger(ImsXmlReader $item){
        $min = empty($item->min) ? 0 : $item->min;
        $max = $item->max;
        return "rand($min, $max)";
    }

    protected function process_null(ImsXmlReader $item){
        return "null";
    }

    protected function process_variable(ImsXmlReader $item){
        $id = $item->identifier;
        return '{' . $id . '}';
    }

    protected function process_baseValue(ImsXmlReader $item){
        $base_type = $item->baseType;
        $value = $item->value();
        return $value;
    }

}





















