<?php

//require_once dirname(dirname(__FILE__)) .'/main.php';

/**
 * Serializer for short-answers questions.
 *
 * University of Geneva
 * @author laurent.opprecht@unige.ch
 *
 */
class ShortanswerSerializer extends QuestionSerializer{

	static function factory($question, $target_root){
		if(!defined('SHORTANSWER') || $question->qtype != SHORTANSWER){
			return null;
		}else{
			return new self($target_root);
		}
	}

	static function factory_subquestion($question, $resource_manager){
		if(!defined("SHORTANSWER") || $question->qtype != SHORTANSWER){
			return new SubquestionSerializerEmpty();
		}else{
			return new ShortanswerSSubquestionSerializer($resource_manager);
		}
	}

	protected $correct_response = null;

	public function __construct($target_root){
		parent::__construct($target_root);
	}

	protected function add_response_declaration(ImsQtiWriter $item, $question){
		$id = ImsQtiWriter::RESPONSE;
		$cardinality =  ImsQtiWriter::CARDINALITY_SINGLE;
		$type = ImsQtiWriter::BASETYPE_STRING;
		$result = $item->add_responseDeclaration($id, $cardinality, $type);
		$this->correct_response = $result->add_correctResponse();
		return $result;
	}

	protected function add_score_processing($response_processing, $question){
		$result = $response_processing->add_responseCondition();
		$response_id = ImsQtiWriter::RESPONSE;
		$outcome_id = ImsQtiWriter::SCORE;
    	$if = $result->add_responseIf();
    	$if->add_isNull()->add_variable($response_id);
    	$if->add_setOutcomeValue($outcome_id)->add_baseValue(ImsQtiWriter::BASETYPE_FLOAT, 0);
    	foreach($question->options->answers as $answer){
    		$score = $answer->fraction * $question->defaultgrade;
    		$elseif = $result->add_responseElseIf();
    		if(ShortanswerUtil::is_regex($answer->answer)){
				$pattern = ShortanswerUtil::translate_regex($answer->answer, $question->options->usecase);
	    		$elseif->add_patternMatch($pattern)->add_variable($response_id);
    		}else{
	    		$match = $elseif->add_stringMatch($question->options->usecase);
	    		$match->add_variable($response_id);
	    		$match->add_baseValue(Qti::BASETYPE_STRING,$answer->answer);
    		}
	    	$elseif->add_setOutcomeValue($outcome_id)->add_baseValue(ImsQtiWriter::BASETYPE_FLOAT, $score);
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
    	$if->add_setOutcomeValue($outcome_id)->add_baseValue(ImsQtiWriter::BASETYPE_IDENTIFIER, 'ID_0');
    	$count = 0;
    	foreach($question->options->answers as $answer){
    		$id = 'ID_' . ++$count;
    		$elseif = $result->add_responseElseIf();
    		if(ShortanswerUtil::is_regex($answer->answer)){
				$pattern = ShortanswerUtil::translate_regex($answer->answer, $question->options->usecase);
	    		$elseif->add_patternMatch($pattern)->add_variable($response_id);
    		}else{
	    		$match = $elseif->add_stringMatch($question->options->usecase);
	    		$match->add_variable($response_id);
	    		$match->add_baseValue(Qti::BASETYPE_STRING, $answer->answer);
    		}
	    	$elseif->add_setOutcomeValue($outcome_id)->add_baseValue(ImsQtiWriter::BASETYPE_IDENTIFIER, $id);
	 	}
    	$else = $result->add_responseElse();
    	$else->add_setOutcomeValue($outcome_id)->add_baseValue(ImsQtiWriter::BASETYPE_IDENTIFIER, 'ID_0');
		return $result;
	}

	protected function add_answer_feedback(ImsQtiWriter $item, $question){
		$count = 0;
		foreach($question->options->answers as $answer){
    		$id = 'ID_' . ++$count;
			if($has_feeback = !empty($answer->feedback)){
				$text = $this->translate_feedback_text($answer->feedback, self::FORMAT_HTML, $question);
				$item->add_modalFeedback(ImsQtiWriter::FEEDBACK, $id, 'show')->add_flow($text);
			}
		}
	}

	protected function add_interaction(ImsQtiWriter $body, $question){
		$expectedLength = 0;
		foreach($question->options->answers as $answer){
			$expectedLength = max($expectedLength, strlen($answer->answer));
		}

		$result = $body->add_extendedTextInteraction(ImsQtiWriter::RESPONSE, $expectedLength, 1, 1);

		foreach($question->options->answers as $answer){
			if($answer->fraction == 1.0 && !ShortanswerUtil::is_regex($answer->answer)){
				$this->correct_response->add_value($answer->answer);
			}
		}
		return $result;
	}

	protected function translate_regex($regex, $is_case_sensitive){
		$result = $is_case_sensitive ? $regex : strtolower($regex);
		$metacharacters = array('.', '\\', '?', '+', '{', '}', '(', ')', '[', ']');
		foreach($metacharacters as $meta){
			$result = str_replace($meta, '\\'.$meta, $result);
		}
		$result = str_replace('*', '.*', $result);
		if(!$is_case_sensitive){
			$letters = array('a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z');
			foreach($letters as $letter){
				$result = str_replace($letter, '['.strtoupper($letter).strtolower($letter).']', $result);
			}
		}
		return $result;
	}

}

/**
 * Utility class for shortanswers.
 * @author lo
 *
 */
class ShortanswerUtil{

	public static function translate_regex($regex, $is_case_sensitive){
		$result = $is_case_sensitive ? $regex : strtolower($regex);
		$star_escape = '_aa_start_aa_';
		$result = str_replace('*', $star_escape, $result);
		$result = preg_quote($result);
		$result = str_replace($star_escape, '.*', $result);
		if(!$is_case_sensitive){
			$letters = array('a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z');
			foreach($letters as $letter){
				$result = str_replace($letter, '['.strtoupper($letter).strtolower($letter).']', $result);
			}
		}
		return $result;
	}

	public static function is_regex($regex){
		return strpos($regex, '*') !== false;
	}
}

/**
 *
 * Used to serialize children questions embeded in a multi-answer/cloze parent question.
 * @author lo
 *
 */
class ShortanswerSSubquestionSerializer extends SubquestionSerializer{

	public function __construct($resource_manager){
		parent::__construct($resource_manager);
	}

	public function add_feedback(ImsQtiWriter $item, $question){
		$feedback_id = $this->feedback_id($question);
		foreach($question->options->answers as $answer){
			if($has_feeback = !empty($answer->feedback)){
    			$answer_id = $this->answer_id($answer);
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
	    		$id = $this->answer_id($answer);
	    		$elseif = $result->add_responseElseIf();
	    		if(ShortanswerUtil::is_regex($answer->answer)){
					$pattern = ShortanswerUtil::translate_regex($answer->answer, $question->options->usecase);
		    		$elseif->add_patternMatch($pattern)->add_variable($response_id);
	    		}else{
		    		$match = $elseif->add_stringMatch($question->options->usecase);
		    		$match->add_variable($response_id);
		    		$match->add_baseValue(Qti::BASETYPE_STRING, $answer->answer);
	    		}
		    	$elseif->add_setOutcomeValue($feedback_id)->add_baseValue(ImsQtiWriter::BASETYPE_IDENTIFIER, $id);
	    		/*
	    		$id = $this->answer_id($answer);
				$pattern = ShortanswerUtil::translate_regex($answer->answer, $question->options->usecase);
	    		$elseif = $result->add_responseElseIf();
		    	$elseif->add_patternMatch($pattern)->add_variable($response_id);
		    	$elseif->add_setOutcomeValue($score_id)->add_baseValue(ImsQtiWriter::BASETYPE_IDENTIFIER, $id);
		    	*/
		 	}
	    	$else = $result->add_responseElse();
	    	$else->add_setOutcomeValue($feedback_id)->add_baseValue(ImsQtiWriter::BASETYPE_IDENTIFIER, 'FEEDBACK_ID_ELSE');
			return $result;
		}else{
			return null;
		}
	}

	public function add_response_declaration(ImsQtiWriter $item, $question){
		$id = $this->response_id($question);
		$cardinality =  ImsQtiWriter::CARDINALITY_SINGLE;
		$type = ImsQtiWriter::BASETYPE_STRING;
		$result = $item->add_responseDeclaration($id, $cardinality, $type);
		$correct_response = $result->add_correctResponse();

		foreach($question->options->answers as $answer){
			if($answer->fraction == 1.0 && !ShortanswerUtil::is_regex($answer->answer)){
				$correct_response->add_value($answer->answer);
			}
		}
		return $result;
	}

	public function add_score_processing(ImsQtiWriter $response_processing, $question){
		$result = $response_processing->add_responseCondition();
		$response_id = $this->response_id($question);
		$score_id = $this->score_id($question);
    	$if = $result->add_responseIf();
    	$if->add_isNull()->add_variable($response_id);
    	$if->add_setOutcomeValue($score_id)->add_baseValue(ImsQtiWriter::BASETYPE_FLOAT, 0);
    	foreach($question->options->answers as $answer){
    		$score = $answer->fraction * $question->defaultgrade;
    		$elseif = $result->add_responseElseIf();
    		if(ShortanswerUtil::is_regex($answer->answer)){
				$pattern = ShortanswerUtil::translate_regex($answer->answer, $question->options->usecase);
	    		$elseif->add_patternMatch($pattern)->add_variable($response_id);
    		}else{
	    		$match = $elseif->add_stringMatch($question->options->usecase);
	    		$match->add_variable($response_id);
	    		$match->add_baseValue(Qti::BASETYPE_STRING,$answer->answer);
    		}
	    	$elseif->add_setOutcomeValue($score_id)->add_baseValue(ImsQtiWriter::BASETYPE_FLOAT, $score);
	    	/*
    		$score = $answer->fraction * $question->defaultgrade;
			$pattern = ShortanswerUtil::translate_regex($answer->answer, $question->options->usecase);
    		$elseif = $result->add_responseElseIf();
	    	$elseif->add_patternMatch($pattern)->add_variable($response_id);
	    	$elseif->add_setOutcomeValue($score_id)->add_baseValue(ImsQtiWriter::BASETYPE_FLOAT, $score);
	    	*/
	 	}
    	$else = $result->add_responseElse();
    	$else->add_setOutcomeValue($score_id)->add_baseValue(ImsQtiWriter::BASETYPE_FLOAT, 0);
		return $result;
	}

	public function add_interaction(ImsQtiWriter $item, $question){
		$expectedLength = 0;
		foreach($question->options->answers as $answer){
			$expectedLength = max($expectedLength, strlen($answer->answer));
		}
		$response_id = $this->response_id($question);
		$result = $item->add_textEntryInteraction($response_id, '', '', $expectedLength);
		return $result;

	}
}












