<?php

//require_once dirname(dirname(__FILE__)) .'/main.php';

/**
 * Serializer for essay questions.
 *
 * University of Geneva
 * @author laurent.opprecht@unige.ch
 *
 */
class EssaySerializer extends QuestionSerializer{

	static function factory($question, $target_root){
		if(!defined("ESSAY") || $question->qtype != ESSAY){
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

	protected function add_interaction($body, $question){
		return $body->add_extendedTextInteraction();
	}

	protected function add_outcome_declaration(ImsQtiWriter $item, $question){
		$this->score = $this->add_score_declaration($item, $question);
		$this->add_general_feedback_declaration($item, $question);
		return $this->score;
	}

	protected function add_response_processing($item, $question){
		return false;
	}

}








