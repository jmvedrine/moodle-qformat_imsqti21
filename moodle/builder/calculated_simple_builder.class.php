<?php

/**
 * Question builder for calculated simple questions.
 *
 * University of Geneva
 * @author laurent.opprecht@unige.ch
 *
 */
class CalculatedSimpleBuilder extends CalculatedBuilder{

    static function factory(QtiImportSettings $settings){

        $item = $settings->get_reader();
        $category = $settings->get_category();

        //if it is a reimport
        if($data = $settings->get_data()){
            if($data->qtype == 'calculatedsimple'){
                return new self($category);
            }else{
                return null;
            }
        }

        $accept = !is_null(CalculatedBuilder::factory($item, $source_root, $target_root)) &&
                  $item->toolName == self::get_tool_name() &&
                  $item->toolVersion >= Qti::get_tool_version() &&
                  $item->label == 'calculatedsimple';
        if($accept){
            return new self($category);
        }else{
            return null;
        }
    }

    public function create_question(){
        $result = parent::create_question();
        $result->qtype = 'calculatedsimple';
        return $result;
    }



}
















