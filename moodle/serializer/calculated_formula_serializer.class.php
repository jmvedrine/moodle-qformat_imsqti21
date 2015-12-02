<?php

/**
 * Translate Moodle calculated formulas to QTI formulas.
 *
 * Tokens regex
 *
 * id = [a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*
 * number = [0-9]*\.?[0-9]+([eE][-+]?[0-9]+)?
 * varref = \{[a-zA-Z_][a-zA-Z0-9_]*\}
 *
 *  Grammar
 *
 *  expression = ["+"|"-"] term {("+"|"-") term} .
 *  term = factor {("*"|"/") factor} .
 *  factor = varref | number | call | "(" expression ")".
 *  call = id "(" expression ")" | id "(" expression "," expression ")"
 *
 *
 * University of Geneva
 * @author laurent.opprecht@unige.ch
 *
 */
class CalculatedFormulaSerializer {

    private $map = array();
    private $success = true;
    private $message = '';
    private $current = '';
    private $text = '';

    /**
     * @var ImsQtiWriter
     */
    private $writer = null;

    public function get_success(){
        return $this->success;
    }

    public function get_message(){
        return $this->message;
    }

    public function parse($text){
        try{
            $this->init($text);
            $result =  $this->get_expression();
            return $result;
        }catch(Exception $e){
            $this->success = false;
            //throw $e;
            return '';
        }
    }

    protected function get_map(){
        if(empty($this->map)){
            $result = array();
            $result['abs'] = 'customOperator';
            $result['acos'] = 'customOperator';
            $result['acosh'] = 'customOperator';
            $result['asin'] = 'customOperator';
            $result['atan'] = 'customOperator';
            $result['ceil'] = 'ceiling';
            $result['cos'] = 'customOperator';
            $result['cosh'] = 'customOperator';
            $result['deg2rad'] = 'customOperator';
            $result['exp'] = 'exp';
            $result['expm1'] = 'customOperator';
            $result['floor'] = 'truncate';
            $result['log'] = 'customOperator';
            $result['log10'] = 'customOperator';
            $result['log1p'] = 'customOperator';
            $result['rad2deg'] = 'customOperator';
            $result['round'] = 'round';
            $result['sin'] = 'customOperator';
            $result['sinh'] = 'customOperator';
            $result['sqrt'] = 'customOperator';
            $result['tan'] = 'customOperator';
            $result['tanh'] = 'customOperator';
            $result['atan2'] = 'customOperator';
            $result['pow'] = 'power';
            $result['pi'] = 3.1415927;
            $result['e'] =  2.7182818;
            $this->map = $result;
        }
        return $this->map;
    }

    protected function translate($key){
        $map = $this->get_map();
        return isset($map[$key]) ? $map[$key] : 'customOperator';
    }

    //GRAMMAR SPECIFIC

    protected function expression(ImsQtiWriter $writer){
        $base_writer = $writer;
        $term_writer = new ImsQtiWriter();
        $term_writer = $this->current == '-' ? $term_writer->add_minus() : $term_writer;
        if($this->current == '-' || $this->current == '+'){
            $this->move_next();
        }
        $noop = true;
        $left = $this->get_term($term_writer);
        while($this->current == '+' || $this->current == '-'){
            if($noop){
                $writer = $writer->add_sum();
                $noop = false;
            }
            $writer->add_flow($left);
            //$writer = $this->current == '-' ? $writer->add_subtract() : $writer->add_sum();
            if($this->current == '-'){
                $this->move_next();
                $term_writer = new ImsQtiWriter();
                $term_writer = $term_writer->add_minus();
                $left = $this->get_term($term_writer);

            }else{
                $this->move_next();
                $left = $this->get_term();
            }
        }
        $writer->add_flow($left);
        //debug($base_writer->get_doc());
        return true;
    }

    protected function term(ImsQtiWriter $writer){
        $left = $this->get_factor();

        $noop = true;
        while($this->current == '/' || $this->current == '*'){
            if($noop){
                $writer = $writer->add_product();
                $noop = false;
            }
            $writer->add_flow($left);
            if($this->current == '/'){
                $this->move_next();
                $term_writer = new ImsQtiWriter();
                $term_writer = $term_writer->add_inverse();
                $left = $this->get_factor($term_writer);
            }else{
                $this->move_next();
                $left = $this->get_factor();
            }
        }
        $writer->add_flow($left);
        return true;
    }

    protected function factor(ImsQtiWriter $writer){
        if($this->number($writer)){
            return true;
        }else if($this->varref($writer)){
            return true;
        }else if($this->call($writer)){
            return true;
        }else if($this->current == '('){
            $this->expect('(');
            $this->expression($writer);
            $this->expect(')');
            return true;
        }else{
            $this->error();
        }
    }

    protected function call(ImsQtiWriter $writer){
        if($this->is_id()){
            $name = $this->translate($this->current);
            if(empty($name)){
                return $this->error();
            }else if($name == 'customOperator'){
                $writer = $writer->add_customOperator($this->current);
            }else if(is_numeric($name)){
                $writer = $writer->add_baseValue(ImsQtiWriter::BASETYPE_FLOAT, $name);
            }
            else{
                $f = 'add_' . $name;
                $writer = $writer->$f();
            }
            $this->move_next();
            $this->expect('(');
            if($this->current != ')'){
                $first = $this->expression($writer);
            }
            while($this->current == ','){
                $this->move_next();
                $second = $this->expression($writer);
            }
            $this->expect(')');
            return true;
        }else{
            return false;
        }
    }

    protected function number(ImsQtiWriter $writer){
        if($this->is_number()){
            $value = $this->current;
            $base_type = ((int)$value) == $value ? ImsQtiWriter::BASETYPE_INTEGER : ImsQtiWriter::BASETYPE_FLOAT;
            $writer->add_baseValue($base_type, $value);
            $this->move_next();
            return true;
        }else{
            return false;
        }
    }

    protected function varref(ImsQtiWriter $writer){
        if($this->is_varref()){
            $writer->add_variable(trim($this->current, '{}'));
            $this->move_next();
            return true;
        }else{
            return false;
        }
    }

    // PARSER CORE

    protected function init($text){
        $this->writer = new ImsQtiWriter();
        $this->success = true;
        $this->message = '';
        $this->text = $this->cleanup($text);
        $this->move_next();
    }

    protected function cleanup($text){
        return preg_replace('#\s+#', '', $text);
    }

    protected function error($token=null){
        $token = empty($token) ? $this->current : $token;
        $this->success = false;
        $this->message = 'Unexpected token: ' . $token;
        throw new Exception($this->message);
        return false;
    }

    protected function accept($token){
        if($this->current == $token){
            $this->move_next();
            return true;
        }else{
            return false;
        }
    }

    protected function expect($token){
        if($this->accept($token)){
            return true;
        }else{
            $this->error($token);
            return false;
        }
    }

    protected function move_next(){
        $token = $this->get_token();
        if(empty($token)){
            return false;
        }else{
            $this->current = $token;
            $this->text = substr($this->text, strlen($token), strlen($this->text)-strlen($token));
            return true;
        }
    }

    //LEXER

    protected function get_token(){
        if($result = $this->get_number()){
            return $result;
        }else if($result = $this->get_id()){
            return $result;
        }else if($result = $this->get_special()){
            return $result;
        }else if($result = $this->get_varref()){
            return $result;
        }else{
            return '';
        }
    }

    protected function is_number(){
        if(empty($this->current)){
            return false;
        }
        $left = substr($this->current, 0, 1);
        return is_number($left);
    }

    protected function is_varref(){
        if(empty($this->current)){
            return false;
        }
        $left = substr($this->current, 0, 1);
        return $left =='{';
    }

    protected function is_id(){
        if(empty($this->current)){
            return false;
        }
        $left = substr($this->current, 0, 1);
        $left = strtolower($left);
        return strpos('abcdefghijklmnopqrstuvwxyz_', $left) !== false;
    }

    protected function is_special(){
        if(empty($this->current)){
            return false;
        }
        $left = substr($this->current, 0, 1);
        return strpos('+-*/%)(,', strtolower($left)) !== false;
    }

    protected function get_special(){
        $left = substr($this->text, 0, 1);
        switch($left){
            case '+':
            case '-':
            case '/':
            case '*':
            case '%':
            case '(':
            case ')':
            //case '<':
            //case '>':
            case ',':
                return $left;
            default:
                return '';
        }
    }

    protected function get_number(){
        $pattern = "/^[0-9]*\.?[0-9]+([eE][-+]?[0-9]+)?/";
        $matches = array();
        if(preg_match($pattern, $this->text, $matches)){
            return $matches[0];
        }else{
            return '';
        }
    }

    protected function get_id(){
        $pattern = "/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*/";
        $matches = array();
        if(preg_match($pattern, $this->text, $matches)){
            return $matches[0];
        }else{
            return '';
        }
    }

    protected function get_varref(){
        $pattern = "/^\{[a-zA-Z_][a-zA-Z0-9_]*\}/";
        $matches = array();
        if(preg_match($pattern, $this->text, $matches)){
            return $matches[0];
        }else{
            return '';
        }
    }

    // END LEXER

    public function __call($name, $arguments){
        $pieces = explode('_', $name);
        if(count($pieces)>1 && $pieces[0] == 'get'){
            $writer = isset($arguments[0]) ? $arguments[0] : new ImsQtiWriter();
            $name = str_replace($pieces[0].'_', '', $name);
            $f = array($this, $name);
            if(call_user_func($f, $writer)){
                $result = $writer->saveXML(false);
                return $result;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }


}





