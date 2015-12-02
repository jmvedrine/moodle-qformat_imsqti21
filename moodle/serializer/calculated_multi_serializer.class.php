<?php


/**
 * Serializer for calculated multi choices.
 *
 * University of Geneva
 * @author laurent.opprecht@unige.ch
 *
 */
class CalculatedMultiSerializer extends CalculatedSerializerBase{

    const PARTIAL_SCORE = 'PARTIAL_SCORE';

    static function factory($question, $target_root){
        if($question->qtype != 'calculatedmulti'){
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

    protected function add_response_declaration(ImsQtiWriter  $item, $question){
          //main response
          $cardinality = $question->options->single ? ImsQtiWriter::CARDINALITY_SINGLE : ImsQtiWriter::CARDINALITY_MULTIPLE;
          $base_type = ImsQtiWriter::BASETYPE_IDENTIFIER;
          $result = $item->add_responseDeclaration(ImsQtiWriter::RESPONSE, $cardinality, $base_type);
          $correct_response = $result->add_correctResponse();

          $this->mapping = $result->add_mapping();
          $count = 0;
          foreach($question->options->answers as $answer){
            $identifier = 'ID_' . ++$count;
            if($answer->fraction == 1){
              $correct_response->add_value($identifier);
            }

          $this->mapping->add_mapEntry($identifier, $answer->fraction * $question->defaultgrade);
          }
          return $result;
    }

  protected function add_outcome_declaration(ImsQtiWriter $item, $question){
    $this->score = $this->add_score_declaration($item, $question);
    $this->add_penalty_declaration($item, $question);
    $this->add_general_feedback_declaration($item, $question);
    $this->add_answer_feedback_declaration($item, $question);

    $this->add_overall_feedback_declaration($item, $question);
    $this->add_partial_score_declaration($item, $question);
  }

  protected function add_partial_score_declaration($item, $question){
    $score = $question->defaultgrade;
    $cardinality = ImsQtiWriter::CARDINALITY_SINGLE;
    $name = self::PARTIAL_SCORE;
    $base_type = is_int($score) ? ImsQtiWriter::BASETYPE_INTEGER : ImsQtiWriter::BASETYPE_FLOAT;
    $result = $item->add_outcomeDeclaration($name, $cardinality, $base_type, $score);
    $result->add_defaultValue()->add_value(0);
    return $result;
  }

  protected function add_score_processing(ImsQtiWriter $response_processing, $question){
      $result = $response_processing->add_standard_response_map_response();
      return $result;
  }

  protected function add_answer_feedback_processing(ImsQtiWriter $processing, $question){
    if($this->has_answer_feedback($question)){
      $result = $processing->add_standard_response_assign_feedback();
      return $result;
    }else{
      return null;
    }
  }

  protected function add_response_processing($item, $question){
    $result = parent::add_response_processing($item, $question);
    $this->add_overall_feedback_processing($result, $question);
    return $result;
  }

  protected function add_overall_feedback_processing(ImsQtiWriter $processing, $question){
    $processing->add_standard_response_map_response(ImsQtiWriter::RESPONSE, self::PARTIAL_SCORE);
    $result = $processing->add_responseCondition();
    $if = $result->add_responseIf();
    $lte = $if->add_lte();
    $lte->add_variable(self::PARTIAL_SCORE);
    $lte->add_baseValue(ImsQtiWriter::BASETYPE_FLOAT, 0);
    $if->add_setOutcomeValue(self::OVERALL_FEEDBACK)->add_baseValue(ImsQtiWriter::BASETYPE_IDENTIFIER, self::FEEDBACK_INCORRECT);
    $elseif = $result->add_responseElseIf();
    $gte = $elseif->add_gte();
    $gte->add_variable(self::PARTIAL_SCORE);
    $gte->add_baseValue(ImsQtiWriter::BASETYPE_FLOAT, $question->defaultgrade);
    $elseif->add_setOutcomeValue(self::OVERALL_FEEDBACK)->add_baseValue(ImsQtiWriter::BASETYPE_IDENTIFIER, self::FEEDBACK_CORRECT);
    $else = $result->add_responseElse();
    $else->add_setOutcomeValue(self::OVERALL_FEEDBACK)->add_baseValue(ImsQtiWriter::BASETYPE_IDENTIFIER, self::FEEDBACK_PARTIALY_CORRECT);
    return $result;
  }

  protected function add_answer_feedback(ImsQtiWriter $item, $question){
    $count = 0;
    foreach($question->options->answers as $answer){
      ++$count;
      $id = 'ID_'. $count;
      if($has_feeback = !empty($answer->feedback)){
        $text = $this->translate_feedback_text($answer->feedback, self::FORMAT_HTML, $question);
        $item->add_modalFeedback(ImsQtiWriter::FEEDBACK, $id, 'show')->add_flow($text);
      }
    }
  }

  protected function add_interaction(ImsQtiWriter $body, $question){
    $max_choices = $question->options->single ? 1 : 0;
    $shuffle = $question->options->shuffleanswers;
    $result = $body->add_choiceInteraction(ImsQtiWriter::RESPONSE, $max_choices, $shuffle);

    $count = 0;
    foreach($question->options->answers as $answer){
      $identifier = 'ID_' . ++$count;
      $choice = $result->add_simpleChoice($identifier);
      $text = $this->translate_question_text($answer->answer, self::FORMAT_HTML, $question);
      $choice->add_flow($text);
    }
    return $result;
  }

}










