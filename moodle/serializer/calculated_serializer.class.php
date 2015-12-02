<?php

//require_once dirname(dirname(__FILE__)) .'/main.php';

/**
 * Serializer for calculated questions.
 *
 * University of Geneva
 * @author laurent.opprecht@unige.ch
 *
 */
class CalculatedSerializer extends CalculatedSerializerBase{

    static function factory($question, $target_root){
        if($question->qtype != 'calculated'){
            return null;
        }else{
            return new self($target_root);
        }
    }

    static function factory_subquestion($question, $resource_manager){
        return new SubquestionSerializerEmpty();
    }

    public function __construct($target_root){
        parent::__construct($target_root);
    }

}
