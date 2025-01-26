<?php
require_once "class.unipager2.php";

class UsersPager extends uniPager2 {
    public function __construct(){
        parent::__construct();

        $this->items_per_page = 100;
        $this->render_func = function($curr_page, $max_page, uniPager2 $pager){
            if ($curr_page >= $max_page)
                $curr_page = $max_page; //return ''; gal changed should still display pageing

            $shift = 12;        // half amount of pages to show
			if($max_page < $shift) { // gal changed check if less pages then max displayed pages - shift
				$range = range(1, $max_page);
			}
			else {
				 if ($curr_page <= $shift)
                $range = range(1, $shift * 2);
				elseif ($curr_page + $shift >= $max_page)
					$range = range($max_page - $shift * 2, $max_page);
				else
					$range = range($curr_page - $shift, $curr_page + $shift);
			}
           

            $pre = $pager->prepareParamString();
            $pre = '?' . ($pre ? $pre . '&' : '') . $pager->paramName(). '=';

            return '<div class="pg-numbers">' . implode('', array_map(function($i) use ($curr_page, $pre) {
                return '<input ' . ($curr_page == $i ? 'class="active" ' : '') . 'value="' . $i . '" readonly="readonly" onclick="window.location.href=\'' . $pre . $i . '\'" />';
            }, $range)) . '</div>';
        };
    }
}
