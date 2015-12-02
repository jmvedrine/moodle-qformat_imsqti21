<?php

/**
 *
 *
 * @copyright (c) 2010 University of Geneva
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author laurent.opprecht@unige.ch
 *
 */
class QuizSerializer extends SerializerBase{

	static function factory($object, $target_root='resources'){
		return new self($target_root);
	}

	public function get_identifier($quiz){
		return 'quiz_' . $quiz->id;
	}

	public function serialize($quiz, $questions){
		$writer = new ImsQtiWriter();
		$quiz_id = self::get_identifier($quiz);
		$test = $writer->add_assessmentTest($quiz_id, $quiz->name, self::get_tool_name(), Qti::get_tool_version());
		$part = $test->add_testPart(null, Qti::NAVIGATION_MODE_LINEAR, Qti::SUBMISSION_MODE_INDIVIDUAL);
		$part->add_itemSessionControl(0, true, true, true, true, true, false);
		$section = $part->add_assessmentSection(null, $quiz->name, false);
		$instruction = $this->translate_question_text($quiz->intro);

		$section->add_rubricBlock(Qti::VIEW_ALL)->add_flow($instruction);

		$this->add_questions($section, $quiz, $questions);
		return $writer->saveXML();
	}

	protected function add_questions(ImsQtiWriter $writer, $quizz, $questions){
		foreach($questions as $path=>$question){
			$filename = basename($path);
			$ref = $writer->add_assessmentItemRef('ID_'.$question->id, $filename, '', $required = true);
			$weight = $this->get_grade($quizz->id, $question->id);
			$ref->add_weight(null, $weight);
		}
	}

	protected function get_grade($quiz_id, $question_id){
		global $DB;
		$result = $DB->get_record('quiz_question_instances', array('quiz'=>$quiz_id, 'question'=>$question_id), '*', IGNORE_MISSING);
		return $result ? $result->grade : 1;
	}

}







?>