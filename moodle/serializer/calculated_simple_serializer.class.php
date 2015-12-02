<?php

//require_once dirname(dirname(__FILE__)) .'/main.php';

/**
 * Serializer for calculated simple questions.
 * 
 * University of Geneva 
 * @author laurent.opprecht@unige.ch
 *
 */
class CalculatedSimpleSerializer extends CalculatedSerializerBase{
		
	static function factory($question, $target_root){
		if(!defined("CALCULATEDSIMPLE") || $question->qtype != CALCULATEDSIMPLE){
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