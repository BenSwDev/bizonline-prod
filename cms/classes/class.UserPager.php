<?php
require_once "class.unipager2.php";

class UserPager extends uniPager2 {
    public function __construct(){
        parent::__construct();

        $this->items_per_page = 50;
        $this->render_func = function($curr_page, $max_page, uniPager2 $pager){
            if ($curr_page > $max_page)
                return '';

            $shift = 5;        // half amount of pages to show

            if ($curr_page <= $shift)
                $range = range(1, min($shift * 2, $max_page));
            elseif ($curr_page + $shift >= $max_page)
                $range = range(max(1, $max_page - $shift * 2), $max_page);
            else
                $range = range($curr_page - $shift, $curr_page + $shift);

            $pre = $pager->prepareParamString();
            $pre = '?' . ($pre ? $pre . '&' : '') . $pager->paramName(). '=';

            return '<div class="pg-numbers">' . implode('', array_map(function($i) use ($curr_page, $pre) {
                return '<input ' . ($curr_page == $i ? 'class="active" ' : '') . 'value="' . $i . '" readonly="readonly" onclick="window.location.href=\'' . $pre . $i . '\'" />';
            }, $range)) . '</div>';
        };
    }
}
