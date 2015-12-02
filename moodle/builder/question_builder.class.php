<?php

/**
 * Base class for all question builders. Builders are responsible to construct a moodle question object.
 * Relies on the import strategies to extract values from the QTI file and on the QTI renderer
 * to render the question's parts.
 *
 * University of Geneva
 * @author laurent.opprecht@unige.ch
 *
 */
class QuestionBuilder{

    public static function is_calculated(ImsXmlReader $item){
        if(!$item->has_templateDeclaration()){
            return false;
        }
        $templates = $item->list_templateDeclaration();
        foreach($templates as $template){
            $base_type = $template->baseType;
            if($base_type != Qti::BASETYPE_FLOAT && $base_type != Qti::BASETYPE_INTEGER){
                return false;
            }
        }
        return true;
    }

    /**
     * @param ImsQtiReader $item
     * @return QuestionBuilder
     */
    public static function factory(QtiImportSettings $settings){
        if($result = EssayBuilder::factory($settings)){
            return $result;
        }else if($result = TruefalseBuilder::factory($settings)){
            return $result;
        }else if($result = MatchingBuilder::factory($settings)){
            return $result;
        }else if($result = NumericalBuilder::factory($settings)){
            return $result;
        }else if($result = DescriptionBuilder::factory($settings)){
            return $result;
        }else if($result = CalculatedSimpleBuilder::factory($settings)){
            return $result;
        }else if($result = CalculatedBuilder::factory($settings)){
            return $result;
        }else if($result = CalculatedMultichoiceBuilder::factory($settings)){
            return $result;
        }else if($result = MultichoiceBuilder::factory($settings)){
            return $result;
        }else if($result = ShortanswerBuilder::factory($settings)){
            return $result;
        }else if($result = ClozeBuilder::factory($settings)){
            return $result;
        }
        return null;
    }

    /**
     * Returns the tool name used to generate qti files.
     * Mostly used to identify if a file is a reimport.
     *
     */
    public static function get_tool_name(){
        return Qti::get_tool_name('moodle');
    }

    /**
     *
     * @param ImsQtiReader $item
     * @return ImsQtiReader
     */
    static function get_main_interaction($item){
        return QtiImportStrategyBase::get_main_interaction($item);
    }

    static function has_score($item){
        return QtiImportStrategyBase::has_score($item);
    }

    static function has_answers($item){
        return QtiImportStrategyBase::has_answers($item);
    }

    static function is_numeric_interaction($item, $interaction){
        return QtiImportStrategyBase::is_numeric_interaction($item, $interaction);
    }

    /**
     *
     * @var QtiImportStrategy
     */
    private $strategy = null;
    private $category = '';

    public function __construct($category){
        $renderer = new QtiPartialRenderer();
        $this->strategy = QtiImportStrategyBase::create_moodle_default_strategy($renderer);
        $this->category = $category;
    }

    public function get_category(){
        return $this->category;
    }

    /**
     * @return QtiImportStrategy
     */
    public function get_strategy(){
        return $this->strategy;
    }

    /**
     * Returns the storage context for the question
     *
     */
    function get_context() {
        if($category = $this->get_category()){
            $contextid = $category->contextid;
            $context = get_context_instance_by_id($contextid);
            return $context;
        }else{
            return null;
        }
    }

    /**
     * Build a question from the file.
     *
     * @param ImsQtiReader $item
     */
    public function build(QtiImportSettings $settings){
        if($data = $settings->get_data()){
            $result = $this->build_moodle($settings);
        }else{
            $result = $this->build_qti($settings);
        }
        $result->questiontext = $this->translate($settings, $result, $result->questiontext);
        return $result;
    }

    /**
     * Build questions using the QTI format. Doing a projection by interpreting the file.
     *
     * @param ImsQtiReader $item
     */
    public function build_qti(QtiImportSettings $settings){
        return null;
    }

    /**
     * Build questions using moodle serialized data. Used for reimport, i.e. from Moodle to Moodle.
     * Used to process data not supported by QTI and to improve performances.
     *
     * @param object $data
     */
    public function build_moodle(QtiImportSettings $settings){
        $data = $settings->get_data();
        $result = $this->create_question();
        if(isset($data->name)){
            $result->name =  $data->name;
        }
        if(isset($data->questiontext)){
            $result->questiontext = $data->questiontext;
        }
        if(isset($data->generalfeedback)){
            $result->generalfeedback = $data->generalfeedback;
        }
        if(isset($data->penalty)){
            $result->penalty = $data->penalty;
        }
        if(isset($data->defaultgrade)){
            $result->defaultgrade = $data->defaultgrade;
        }
        if(isset($data->options)){
            if(isset($data->options->correctfeedback)){
                $result->correctfeedback = $this->format_text($data->options->correctfeedback);
            }
            if(isset($data->options->partiallycorrectfeedback)){
                $result->partiallycorrectfeedback = $this->format_text($data->options->partiallycorrectfeedback);
            }
            if(isset($data->options->incorrectfeedback)){
                $result->incorrectfeedback = $this->format_text($data->options->incorrectfeedback);
            }
        }

        return $result;
    }

    protected function create_question($data = null){
        $default = new qformat_default();
        $result = $default->defaultquestion();
        $result->usecase = 0; // Ignore case
        $result->image = ''; // No image
        $result->questiontextformat = FORMAT_HTML; //HTML
        $result->answer = array();
        $result->context = $this->get_context();
        $category = $this->get_category();
        $result->category = $category ? $category->name : '';
        $result->resources = array();
        return $result;
    }

    // TRANSLATE RELATIVE PATH

    protected function translate(QtiImportSettings $settings, $question, $text){
        $pattern = '/src="[^"]*"/';
        $matches = array();
        preg_match_all($pattern, $text, $matches, PREG_SET_ORDER);
        foreach($matches as $match){
            $match = reset($match);
            $match = str_replace('src="', '', $match);
            $match = trim($match, '"');
            $replace = $this->translate_path($match);
            $text = str_ireplace('src="'. $match . '"','src="'.  $replace. '"', $text);
            $name = end(explode('/', $match));
            $question->resources[$name] = $settings->get_directory() . $match;
        }
        return $text;
    }

    protected function translate_path($path){
        if(! $this->is_path_relative($path)){
            return $path;
        }

        $name = end(explode('/', $path));
        $result = '@@PLUGINFILE@@/' . $name;
        return $result;
    }

    public function is_path_relative($path){
        return strlen($path)<5 || strtolower(substr($path, 0, 4)) != 'http';
    }

    // UTIL

    protected function get_feedback(ImsQtiReader $item, ImsQtiReader $interaction, $answer, $filter_out){
        $result =  $this->get_feedbacks($item, $interaction, $answer, $filter_out);
        $result =  implode('<br/>', $result);
        return $result;
    }

    protected function get_instruction(ImsQtiReader $item, $role = Qti::VIEW_ALL){
        $result = $this->get_rubricBlock($item, $role);
        $result = implode('<br/>', $result);
        return $result;
    }

    protected function get_fraction($item, $interaction, $answer, $default_grade){
        $default_grade = empty($default_grade) ? 1 : $default_grade;
        $score = $this->get_score($item, $interaction, $answer);
        $result = MoodleUtil::round_to_nearest_grade($score/$default_grade);
        return $result;
    }

    /**
     * Return a formatted text entry ready to be processed
     *
     * @param string $text text
     * @param int $format text's format
     * @param int $itemid existing item id (null for new entries)
     */
    protected function format_text($text, $format = FORMAT_HTML, $itemid = null){
        return array(   'text' => $text,
                        'format' => FORMAT_HTML,
                        'itemid' => null);
    }

    public function __call($name, $arguments) {
        $f = array($this->strategy, $name);
        if(is_callable($f)){
            return call_user_func_array($f, $arguments);
        }else{
            throw new Exception('Unknown method: '. $name);
        }
    }

}










