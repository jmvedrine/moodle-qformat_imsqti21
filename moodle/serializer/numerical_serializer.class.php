<?php

//require_once dirname(dirname(__FILE__)) .'/main.php';

/**
 * Serializer for numerical questions.
 *
 * University of Geneva
 * @author laurent.opprecht@unige.ch
 *
 */
class NumericalSerializer extends NumericalSerializerBase{

	static function factory($question, $target_root){
		if(!defined("NUMERICAL") || $question->qtype != NUMERICAL){
			return null;
		}else{
			return new self($target_root);
		}
	}

	static function factory_subquestion($question, $resource_manager){
		if(!defined("NUMERICAL") || $question->qtype != NUMERICAL){
			return new SubquestionSerializerEmpty();
		}else{
			return new NumericalSubquestionSerializer($resource_manager);
		}
	}

	public function __construct($target_root){
		parent::__construct($target_root);
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
    		if(is_numeric($answer->answer)){ //could be set to *
	    		$score = $answer->fraction * $question->defaultgrade;
				$tolerance_mode = ImsQtiWriter::TOLERANCE_MODE_ABSOLUTE;
	    		$elseif = $result->add_responseElseIf();
		    	$equal = $elseif->add_equal($tolerance_mode, $answer->tolerance, $answer->tolerance);
		    	$equal->add_baseValue(ImsQtiWriter::BASETYPE_FLOAT, $answer->answer);
		    	$product = $equal->add_product();
		    	$product->add_variable($response_id);
		    	$product->add_variable(self::UNIT_MULTIPLIER);
		    	$elseif->add_setOutcomeValue($outcome_id)->add_baseValue(ImsQtiWriter::BASETYPE_FLOAT, $score);
    		}
	 	}
    	$else = $result->add_responseElse();
    	$else->add_setOutcomeValue($outcome_id)->add_baseValue(ImsQtiWriter::BASETYPE_FLOAT, 0);
		return $result;
	}

	protected function add_answer_feedback_processing($response_processing, $question){
		$result = $response_processing->add_responseCondition();
		$response_id = ImsQtiWriter::RESPONSE;
		$outcome_id = ImsQtiWriter::FEEDBACK;
    	$if = $result->add_responseIf();
    	$if->add_isNull()->add_variable($response_id);
    	$if->add_setOutcomeValue($outcome_id)->add_baseValue(ImsQtiWriter::BASETYPE_IDENTIFIER, 'FEEDBACK_ID_ELSE');
    	$count = 0;
    	foreach($question->options->answers as $answer){
    		$id = 'FEEDBACK_ID_' . ++$count;
    		if(is_numeric($answer->answer)){ //could be set to *
				$tolerance_mode = ImsQtiWriter::TOLERANCE_MODE_ABSOLUTE;
	    		$elseif = $result->add_responseElseIf();
		    	$equal = $elseif->add_equal($tolerance_mode, $answer->tolerance, $answer->tolerance);
		    	$equal->add_baseValue(ImsQtiWriter::BASETYPE_FLOAT, $answer->answer);
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
			$result = $body->add_extendedTextInteraction(ImsQtiWriter::RESPONSE, '', 1, 1);
			//$result = $body->add_textEntryInteraction();
		}else{
			$result = $body->add_extendedTextInteraction(ImsQtiWriter::RESPONSE, '', 1, 1);
			//$result = $body->add_textEntryInteraction();
			$this->add_unit($body, $question);
		}
		$instructions = $question->options->instructions;
		if(!empty($instructions)){
			$body->add_rubricBlock(ImsQtiWriter::VIEW_ALL)->add_flow($instructions);
		}
		return $result;
	}
}

/**
 *
 * Used to serialize children questions embeded in a multi-answer/cloze parent question.
 * @author lo
 *
 */
class NumericalSubquestionSerializer extends SubquestionSerializer{

	public function __construct($resource_manager){
		parent::__construct($resource_manager);
	}

	public function add_feedback(ImsQtiWriter $item, $question){
		$feedback_id = $this->feedback_id($question);
	 	foreach($question->options->answers as $answer){
		    $answer_id = $this->answer_id($answer);
		    if($has_feeback = !empty($answer->feedback)){
		    	$text = $this->translate_feedback_text($answer->feedback, self::FORMAT_HTML, $question);
		        $item->add_modalFeedback($feedback_id, $answer_id, 'show')->add_flow($text);
		    }
		}
	}

	public function add_feedback_processing(ImsQtiWriter $processing, $question){
		if($this->has_answer_feedback($question)){
			$result = $processing->add_responseCondition();
			$response_id = $this->response_id($question);
			$feedback_id = $this->feedback_id($question);
	    	$if = $result->add_responseIf();
	    	$if->add_isNull()->add_variable($response_id);
	    	$if->add_setOutcomeValue($feedback_id)->add_baseValue(ImsQtiWriter::BASETYPE_IDENTIFIER, 'FEEDBACK_ID_ELSE');
	    	foreach($question->options->answers as $answer){

	    		$answer_id = $this->answer_id($answer);
	    		if(is_numeric($answer->answer)){ //could be set to *
					$tolerance_mode = ImsQtiWriter::TOLERANCE_MODE_ABSOLUTE;
		    		$elseif = $result->add_responseElseIf();
			    	$equal = $elseif->add_equal($tolerance_mode, $answer->tolerance, $answer->tolerance);
			    	$equal->add_baseValue(ImsQtiWriter::BASETYPE_FLOAT, $answer->answer);
			    	$equal->add_variable($response_id);
		    		$elseif->add_setOutcomeValue($feedback_id)->add_baseValue(ImsQtiWriter::BASETYPE_IDENTIFIER, $answer_id);
	    		}
		 	}
		 	$else = $result->add_responseElse();
	    	$else->add_setOutcomeValue($feedback_id)->add_baseValue(ImsQtiWriter::BASETYPE_IDENTIFIER, 'FEEDBACK_ID_ELSE');

			return $result;
		}else{
			return null;
		}
	}

	public function add_response_declaration(ImsQtiWriter $item, $question){
		$cardinality = ImsQtiWriter::CARDINALITY_SINGLE;
		$identity = $this->response_id($question);
		$result = $item->add_responseDeclaration($identity, $cardinality, ImsQtiWriter::BASETYPE_FLOAT);
		$correct_response = $result->add_correctResponse();

    	foreach($question->options->answers as $answer){
      		$answer_id = $this->answer_id($answer);
      		if($is_correct = $answer->fraction == 1){
        		$correct_response->add_value($answer->answer);
      		}
    	}

		return $result;
	}

	public function add_score_processing(ImsQtiWriter $response_processing, $question){
		$result = $response_processing->add_responseCondition();
		$response_id = $this->response_id($question);
		$outcome_id = $this->score_id($question);
    	$if = $result->add_responseIf();
    	$if->add_isNull()->add_variable($response_id);
    	$if->add_setOutcomeValue($outcome_id)->add_baseValue(ImsQtiWriter::BASETYPE_FLOAT, 0);
    	foreach($question->options->answers as $answer){
    		if(is_numeric($answer->answer)){ //could be set to *
	    		$score = $answer->fraction * $question->defaultgrade;
				$tolerance_mode = ImsQtiWriter::TOLERANCE_MODE_ABSOLUTE;
	    		$elseif = $result->add_responseElseIf();
		    	$equal = $elseif->add_equal($tolerance_mode, $answer->tolerance, $answer->tolerance);
		    	$equal->add_baseValue(ImsQtiWriter::BASETYPE_FLOAT, $answer->answer);
		    	$equal->add_variable($response_id);
		    	$elseif->add_setOutcomeValue($outcome_id)->add_baseValue(ImsQtiWriter::BASETYPE_FLOAT, $score);
    		}
	 	}
    	$else = $result->add_responseElse();
    	$else->add_setOutcomeValue($outcome_id)->add_baseValue(ImsQtiWriter::BASETYPE_FLOAT, 0);
		return $result;
	}

	public function add_interaction(ImsQtiWriter $item, $question){
		$response_id = $this->response_id($question);
		$result = $item->add_textEntryInteraction($response_id);
		$instructions = $question->options->instructions;
		if(!empty($instructions)){
			$body->add_rubricBlock(ImsQtiWriter::VIEW_ALL)->add_flow($instructions);
		}
		return $result;

	}
}






