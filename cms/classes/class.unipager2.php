<?php
/**
 * Class uniPager2
 * requires PHP 7.0 or later
 */
abstract class uniPager2 {
    protected static $_method_list = ['GET', 'POST', 'REQUEST'];

    protected $_param  = 'pg';
    protected $_method = 'GET';
    protected $_skip_params = [];

    public $items_total    = 0;
    public $items_per_page = 50;
    public $current_page   = 0;
    public $render_func    = null;

    protected function _reset(){
        switch($this->_method){
            case 'GET': $this->currentPage($_GET[$this->_param] ?? 1); return $this;
            case 'POST': $this->currentPage($_POST[$this->_param] ?? 1); return $this;
            case 'REQUEST': $this->currentPage($_REQUEST[$this->_param] ?? 1); return $this;
            default: return $this;      // shouldn't happen
        }
    }

    public function prepareParamString($url = null)
    {
        if ($this->_method == 'POST')
            return '';

        parse_str(parse_url($url ?: $_SERVER['REQUEST_URI'], PHP_URL_QUERY), $query);

        if (count($query)){
            $tmp = array_merge($this->_skip_params, [$this->_param]);
            $query = array_diff_key($query, array_combine($tmp, $tmp));
        }

        return http_build_query($query);
    }

    public function __construct($param = '', $method = 'GET'){
        if ($param)
            $this->paramName($param);
        $this->method($method);
    }

    public function paramName(...$name){
        if (!count($name) || !strlen($name[0]))
            return $this->_param;

        $this->_param = $name[0];
        return $this->_reset();
    }

    public function method(...$name){
        if (!count($name) || !strlen($name[0]))
            return $this->_method;

        $key = array_search(strtoupper($name[0]), self::$_method_list);
        if ($key === false)
            throw new Exception('Unknown method "' . $name[0] . '"');

        $this->_method = self::$_method_list[$key];
        return $this->_reset();
    }

    public function currentPage(...$page){
        if (!count($page))
            return $this->current_page;

        $this->current_page = max(intval($page[0]), 1);
        return $this;
    }

    public function addSkip($skip){
        if (!in_array(trim($skip), $this->_skip_params))
            $this->_skip_params[] = trim($skip);
        return $this;
    }

    public function sqlPosition(){
        return $this->items_per_page * (max($this->current_page, 1) - 1);
    }

    public function sqlLimit(){
        return ' LIMIT ' . $this->sqlPosition() . ', ' . intval($this->items_per_page);
    }

    public function render(){
        if (!is_callable($this->render_func))
            return '';
        return call_user_func($this->render_func, max(intval($this->current_page), 1), ceil($this->items_total / $this->items_per_page), $this);
    }

    // deprecated. use ->method() instead (keeping it for back-compatibility)
    public function setMethod($name){
        return $this->method($name);
    }

    // deprecated. use ->paramName() instead (keeping it for back-compatibility)
    public function setName($name){
        return $this->paramName($name);
    }

    // deprecated. use ->sqlPosition instead (keeping it for back-compatibility)
    public function startNum(){
        return $this->sqlPosition();
    }

    // deprecated. access ->items_per_page property directly (keeping it for back-compatibility)
    public function setPage($num){
        $this->items_per_page = intval($num);
    }

    // deprecated. access ->items_total property directly (keeping it for back-compatibility)
    public function setTotal($num){
        $this->items_total = intval($num);
    }

    // deprecated. use ->render() instead (keeping it for back-compatibility)
    public function setPager(){
        return $this->render();
    }
}
