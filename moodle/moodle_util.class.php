<?php

/**
 * Facade for moodle.
 *
 * University of Geneva
 * @author laurent.opprecht@unige.ch
 *
 */
class MoodleUtil{

    public static function round_to_nearest_grade($value){
        $options = get_grade_options();
        $grades = $options->gradeoptionsfull;
        $result = 0;
        foreach($grades as $grade=>$name){
            if(abs($grade-$value)<abs($result-$value)){
                $result = $grade;
            }
        }
        return $result;
    }

    public static function archive_directory($directory_path, $file_path){
        $files = array();
        $directory_path = rtrim($directory_path, '/');
        $entries = scandir($directory_path);
        foreach($entries as $entry){
            if($entry !='.' && $entry !='..'){
                $path = $directory_path . '/'. $entry;
                $files[$entry] = $path;
            }
        }
        $zipper = new zip_packer();
        $result = $zipper->archive_to_pathname($files, $file_path);
        return $result;
    }

    public static function extract_archive($archive_path, $to_path=''){
        $to_path = empty($to_path) ? MoodleUtil::create_temp_directory() : $to_path;
        $zipper = new zip_packer();
        if($zipper->extract_to_pathname($archive_path, $to_path)){
            return $to_path;
        }else{
            return false;
        }
    }

    /**
     *
     * @param sring $url
     * @return stored_file
     */
    public static function copy_to_file($url, $to){
        if(strpos($url, 'moodle/file.php/') !== false){
            ///moodle/file.php/1/extented/images/postcard.png
            $url = strstr($url, 'moodle/file.php/');
            $url = str_replace('moodle/file.php/', '', $url);
            $pieces = explode('/', $url);
            $courseid = (int)array_shift($pieces);
            $relativepath = '/'.implode('/', $pieces);
            $context = context_course::instance($courseid);
            $fullpath = $context->id.'course_content0'.$relativepath;
            $fs = get_file_storage();
            $file = $fs->get_file_by_hash(sha1($fullpath));
            $file->copy_content_to($to);
        }else if(strpos($url, 'http') !== false){
            file_put_contents($to, file_get_contents($url));
        }


    }

    public static function get_current_language(){
        $result = current_language();
        $result = explode('_', $result);
        $result = reset($result);
        return $result;
    }

    public static function create_temp_directory(){
        global $CFG, $USER;
        // Create a temporary directory for the files.
        $uniquecode = time();
        $tempdir = 'qformat_imsqti21/' . $USER->id . '/' . $uniquecode;
        if (!$path = make_temp_directory($tempdir)) {
            throw new moodle_exception('cannotcreatepath', 'question', '', $path);
        }
        return $path . '/';
    }

    static function get_catalog_name(){
        $server_name = $_SERVER['SERVER_NAME'];
        $server_name = empty($server_name) ? '' : ":$server_name";
        $result = "moodle$server_name";
        return $result;
    }

}