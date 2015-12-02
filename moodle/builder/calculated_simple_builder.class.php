<?php

/**
 * Question builder for CALCULATEDSIMPLE questions.
 *
 * University of Geneva
 * @author laurent.opprecht@unige.ch
 *
 */
class CalculatedSimpleBuilder extends CalculatedBuilder{

    static function factory(QtiImportSettings $settings){
        if(!defined('CALCULATEDSIMPLE')){
            return null;
        }

        $item = $settings->get_reader();
        $category = $settings->get_category();

        //if it is a reimport
        if($data = $settings->get_data()){
            if($data->qtype == CALCULATEDSIMPLE){
                return new self($category);
            }else{
                return null;
            }
        }

        $accept = defined('CALCULATEDSIMPLE' || !self::has_score($item)) &&
                  !is_null(CalculatedBuilder::factory($item, $source_root, $target_root)) &&
                  $item->toolName == self::get_tool_name() &&
                  $item->toolVersion >= Qti::get_tool_version() &&
                  $item->label == CALCULATEDSIMPLE;
        if($accept){
            return new self($category);
        }else{
            return null;
        }
    }

    public function create_question(){
        $result = parent::create_question();
        $result->qtype = CALCULATEDSIMPLE;
        return $result;
    }



}
















