<?php
function normalize_array2(&$array){
    $true_array = true;
    $i = 0;

    foreach($array as $key => &$val){
        $true_array = ($true_array && $key === $i++);
        if (is_array($val))
            normalize_array2($val);
    }

    $true_array ? sort($array) : ksort($array);
}

class DynamicMenu {
    const MENU_TYPE_ATTRIBUTE = 3;
    const MENU_TYPE_CITY      = 2;
    const MENU_TYPE_AREA      = 1;
    const MENU_TYPE_MAIN_AREA = 4;

    public static $menuType = 50;

    private static function _addMenuParam($prm, $row){
        switch($row['menuSearch']){
            case self::MENU_TYPE_ATTRIBUTE:
                $prm['attr'] = array_merge($prm['attr'] ?? [], [intval($row['menuPage'])]);
                break;

            case self::MENU_TYPE_CITY:
                if ($prm['city'])
                    return false;
                $prm['city'] = intval($row['menuPage']);
                break;

            case self::MENU_TYPE_AREA:
                if ($prm['area'])
                    return false;
                $prm['area'] = intval($row['menuPage']);
                break;

            case self::MENU_TYPE_MAIN_AREA:
                if ($prm['main_area'])
                    return false;
                $prm['main_area'] = intval($row['menuPage']);
                break;

            default: return false;
        }

        return $prm;
    }

    public static function build($params = []){
        $types = udb::full_list("SELECT `id`, `topMenu` FROM `roomTypes` WHERE 1 ORDER BY `id`");
        $base  = [];

        foreach($types as $type)
            if ($type['topMenu'])
                $base[] = $type['id'];

        $baseType = (isset($params['type']) && in_array($params['type'], $base)) ? $params['type'] : intval($base[0]);
        $subType  = ['menuID' => 0];

        if ($params['city'])
            $subType = [
                'menuID' => udb::single_value("SELECT `menuID` FROM `menu` WHERE `menuShow` = 1 AND `menuType` = " . self::$menuType . " AND `menuSearch` = " . self::MENU_TYPE_CITY . " AND `menuPage` = " . $params['city']) ?: 0,
                'title'  => udb::single_value("SELECT `TITLE` FROM `settlements_text` WHERE `settlementID` = " . $params['city'] . " AND `LangID` = " . ActivePage::$langID),
                'prm'    => ['city' => $params['city'], 'type' => 0]
            ];
        elseif ($params['area'])
            $subType = [
                'menuID' => udb::single_value("SELECT `menuID` FROM `menu` WHERE `menuShow` = 1 AND `menuType` = " . self::$menuType . " AND `menuSearch` = " . self::MENU_TYPE_AREA . " AND `menuPage` = " . $params['area']) ?: 0,
                'title'  => udb::single_value("SELECT `TITLE` FROM `areas_text` WHERE `areaID` = " . $params['area'] . " AND `LangID` = " . ActivePage::$langID),
                'prm'    => ['area' => $params['area'], 'type' => 0]
            ];
        elseif ($params['main_area'])
            $subType = [
                'menuID' => udb::single_value("SELECT `menuID` FROM `menu` WHERE `menuShow` = 1 AND `menuType` = " . self::$menuType . " AND `menuSearch` = " . self::MENU_TYPE_MAIN_AREA . " AND `menuPage` = " . $params['main_area']) ?: 0,
                'title'  => udb::single_value("SELECT `TITLE` FROM `main_areas_text` WHERE `main_areaID` = " . $params['main_area'] . " AND `LangID` = " . ActivePage::$langID),
                'prm'    => ['main_area' => $params['main_area'], 'type' => 0]
            ];
        elseif (is_array($params['attr']) && count($params['attr'])){
            $tmp = udb::single_row("SELECT a.menuID, a.menuPage FROM `menu` AS `a` INNER JOIN `menu` AS `b` ON (a.menuParent = b.menuID) 
                                       WHERE a.menuType = " . self::$menuType . " AND a.menuSearch = " . self::MENU_TYPE_ATTRIBUTE . " AND a.menuPage IN (" . implode(',', $params['attr']) . ") AND a.menuShow = 1
                                        ORDER BY (a.menuOrder + 1) * (b.menuOrder + 1)
                                         LIMIT 1") ?: 0;
            if ($tmp)
                $subType = [
                    'menuID' => $tmp['menuID'],
                    'title'  => udb::single_value("SELECT `defaultName` FROM `attributes_langs` WHERE `attrID` = " . $tmp['menuPage'] . " AND `langID` = " . ActivePage::$langID . " AND `domainID` = 0"),
                    'prm'    => ['attr' => [intval($tmp['menuPage'])], 'type' => 0]
                ];
        }

        if ($subType['menuID']){
?>
        <li class="Fili expandable">
            <a class="fiA" href="<?=ActivePage::showAlias(ActivePage::$page['table'], ActivePage::$page['ref'])?>"><?=$subType['title']?></a>
            <ul class="subUl">
<?php
            foreach($types as $type){
                $alias = udb::single_row("SELECT alias.* FROM `search` INNER JOIN `alias_text` AS `alias` ON (alias.ref = search.id AND alias.table = 'search' AND alias.langID = " . ActivePage::$langID . " AND alias.domainID = 1)
                                            WHERE `data` = '" . json_encode(array_merge($subType['prm'], ['type' => intval($type['id'])])) . "' AND `active` = 1");
?>
                <li class="subLi"><a class="subLink" href="<?=ActivePage::compileAlias($alias)?>"><?=$alias['title']?></a></li>
<?php
            }
?>
            </ul>
			 <div class="openMenuTab"><i class="icon-lang_arrow"></i></div>
        </li>
<?php
        }

        foreach($base as $typeID){
            $baseAlias = udb::single_row("SELECT alias.* FROM `search` INNER JOIN `alias_text` AS `alias` ON (alias.ref = search.id AND alias.table = 'search' AND alias.langID = " . ActivePage::$langID . " AND alias.domainID = 1)
                                            WHERE `data` = '" . json_encode(['type' => intval($typeID)]) . "' AND `active` = 1");

            if ($typeID == $baseType){
                $que = "SELECT menu.menuID, menu.menuParent, menu.menuOrder, menu.menuTitle, alias.*
                        FROM `menu` LEFT JOIN `cache_dynamic_menu` AS `cache` ON (menu.menuID = cache.menuID AND cache.typeID = " . $typeID . " AND cache.subID = " . $subType['menuID'] . ")
                            LEFT JOIN `alias_text` AS `alias` ON (alias.ref = cache.searchID AND alias.table = 'search' AND alias.langID = " . ActivePage::$langID . " AND alias.domainID = 1)
                        WHERE menu.menuType = " . self::$menuType . " AND menu.menuShow = 1 AND (cache.searchID <> 0 OR menu.menuSearch = 0)";
                $subMenu = udb::key_list($que, 'menuParent');
            } else
                $subMenu = false;
?>
        <li class="Fili<?=($subMenu ? ' expandable' : '')?>">
            <a class="fiA" href="<?=ActivePage::compileAlias($baseAlias)?>"><?=$baseAlias['title']?></a>
<?php
            if($subMenu){
?>
                <ul class="subUl">
<?php
                foreach($subMenu[0] as $row){
                    if (isset($subMenu[$row['menuID']]) && count($subMenu[$row['menuID']])){
?>
                    <li class="subLi expandable">
                       
                        <a class="subLink"><?=Dictionary::translate($row['menuTitle'])?></a>
                        <ul>
<?php
                    foreach($subMenu[$row['menuID']] as $sub)
                        echo '<li><a href="' , ActivePage::compileAlias($sub) , '">' , $sub['title'] , '</a></li>';
?>
                        </ul>
						 <div class='openMenuTab nolink'><i class='icon-lang_arrow'></i></div>
                    </li>
<?php
                    }
                }
?>
                </ul>
				 <?=($subMenu ? '<div class="openMenuTab"><i class="icon-lang_arrow"></i></div>' : '')?>
<?php
            } 
           
?>
        </li>
<?php
        }
    }


    public static function cache(){
        $menu = udb::full_list("SELECT * FROM `menu` WHERE `menuType` = " . self::$menuType . " ORDER BY `menuOrder`");
        $base = udb::single_column("SELECT `id` FROM `roomTypes` WHERE `topMenu` > 0 ORDER BY `topMenu`");

        $que = [];

        foreach($base as $typeID){
            $bprm = ['type' => intval($typeID)];

            foreach($menu as $row){
                if ($prm = self::_addMenuParam($bprm, $row)){
                    normalize_array2($prm);
                    $que[] = "SELECT " . $row['menuID'] . " AS `mid`, " . $typeID . " AS `type`, 0 AS `sub`, IFNULL(search.id, 0) AS `sid` FROM `search` WHERE `data` = '" . json_encode($prm) . "' AND `active` = 1";

                    foreach($menu as $sub){
                        if ($sub['menuID'] != $row['menuID'] && ($sprm = self::_addMenuParam($prm, $sub))){
                            normalize_array2($sprm);
                            $que[] = "SELECT " . $row['menuID'] . " AS `mid`, " . $typeID . " AS `type`, " . $sub['menuID'] . " AS `sub`, IFNULL(search.id, 0) AS `sid` FROM `search` WHERE `data` = '" . json_encode($sprm) . "' AND `active` = 1";
                        }
                    }
                }
            }
        }

        foreach(array_chunk($que, 200) as $part){
            $que = "INSERT INTO `cache_dynamic_menu`(`menuID`, `typeID`, `subID`, `searchID`)
                            " . implode(' UNION ALL ', $part) . "
                        ON DUPLICATE KEY UPDATE `searchID` = VALUES(`searchID`)";
            udb::query($que);
        }
    }
}
