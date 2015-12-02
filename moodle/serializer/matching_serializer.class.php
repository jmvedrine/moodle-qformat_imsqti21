<?php

//require_once dirname(dirname(__FILE__)) .'/main.php';

/**
 * Serializer for matching questions.
 *
 * University of Geneva
 * @author laurent.opprecht@unige.ch
 *
 */
class MatchingSerializer extends QuestionSerializer{

	static function factory($question, $target_root){
		if(!defined("MATCH") || $question->qtype != MATCH){
			return null;
		}else{
			return new self($target_root);
		}
	}

	static function factory_subquestion($question, $resource_manager){
		return new SubquestionSerializerEmpty();
	}

	protected $correct_response = null;
	protected $mapping = null;

	public function __construct($target_root){
		parent::__construct($target_root);
	}

	protected function add_response_declaration(ImsQtiWriter $item, $question){
		$id = ImsQtiWriter::RESPONSE;
		$cardinality =  ImsQtiWriter::CARDINALITY_MULTIPLE;
		$type = ImsQtiWriter::BASETYPE_DIRECTEDPAIR;
		$result = $item->add_responseDeclaration($id, $cardinality, $type);
		$this->correct_response = $result->add_correctResponse();
		$this->mapping = $result->add_mapping('', '', 0);
		return $result;
	}

	protected function add_score_processing($response_processing, $question){
		return $response_processing->add_standard_response_map_response();
	}

	protected function add_interaction(ImsQtiWriter $body, $question){
		$question_count = 0;
		foreach($question->options->subquestions as $subquestion){
			if(!empty($subquestion->questiontext)){
				$question_count++;
			}
		}

		$result = $body->add_matchInteraction(ImsQtiWriter::RESPONSE, $question_count, $question->options->shuffleanswers);
		$questions = $result->add_simpleMatchSet();
		$answers = $result->add_simpleMatchSet();

		$question_score = $question->defaultgrade / $question_count;

		$count = 0;
		foreach($question->options->subquestions as $subquestion){
			++$count;
			$question_id = 'Q_' . $count;
			$answer_id = 'A_'. $count;
			if($has_question = !empty($subquestion->questiontext)){
				$questions->add_simpleAssociableChoice($question_id, false, array(), 1)->add_flow($subquestion->questiontext);
			}
			if($has_answer = !empty($subquestion->answertext)){
				$answers->add_simpleAssociableChoice($answer_id, false, array(), 1)->add_flow($subquestion->answertext);
			}
			if($has_question && $has_answer){
				$key = "$question_id $answer_id";
				$this->mapping->add_mapEntry($key, $question_score);
				$this->correct_response->add_value($key);
			}
		}
		return $result;
	}


}








