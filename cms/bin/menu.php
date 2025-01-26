<?php
return array(
    'main' => array(
        "name"  => "ראשי",
        "href"  => "index.php",
        "icon"  => "/images/dash.png",
        "level" => "10",
        "sub"   => array()
    ),
	array(
        "name"  => "ניהול ראשי",
        "href"  => "",
        "icon"  => "/images/content.png",
        "level" => "10",
        "sub"   => array(
			'redirect' => array(
                "name"  => "הפניות 301",
                "href"  => "redirects.php?type=1&domainID=1"
            ),
			'searchResults' => array(
                "name"  => "תוצאות חיפוש",
                "href"  => "moduls/main/search/index.php?domainID=1"
            ),
			'classMainPage' => array(
                "name"  => "מחלקות דף ראשי",
                "href"  => "moduls/main/categoriesMainPage/index.php?domainID=1&langID=1"
            ),
			'domains' => array(
                "name"  => "דומיינים",
                "href"  => "moduls/main/domains/table.php"
            ),
			'index_con' => array(
                "name"  => "עמוד ראשי - למי מתאים?",
                "href"  => "moduls/pages/contentPages/index.php?type=150"
            ),
			'index_need' => array(
                "name"  => "עמוד ראשי - כל מה שצריך",
                "href"  => "moduls/pages/contentPages/index.php?type=160"
            ),
			'index_revs' => array(
                "name"  => "עמוד ראשי - חוות דעת",
                "href"  => "moduls/pages/contentPages/index.php?type=170"
            )
		)
    ),

	array(
        "name"  => "ניהול אזורים ישובים",
        "href"  => "",
        "icon"  => "/images/content.png",
        "level" => "10",
        "sub"   => array(
			'mainAreas' => array(
                "name"  => "אזורים ראשיים",
                "href"  => "moduls/locations/mainareas.php"
            ),
			'areas' => array(
                "name"  => "אזורים",
                "href"  => "moduls/locations/areas.php"
            ),
			'cities' => array(
                "name"  => "ישובים",
                "href"  => "moduls/locations/cities.php"
            )
		)
    ),
	array(
        "name"  => "ניהול תפריטים",
        "href"  => "",
        "icon"  => "/images/menus.png",
        "level" => "10",
        "sub"   => array(
			'topMenu' => array(
                "name"  => "תפריט עליון",
                "href"  => "menus/index.php?langID=1&menuType=1&domainID=1"
            ),
			'footerMenu' => array(
                "name"  => "תפריט פוטר",
                "href"  => "menus/index.php?langID=1&menuType=2&domainID=1"
            ),
			'extraMenu' => array(
                "name"  => "extraMenu",
                "href"  => "menus/index.php?langID=1&menuType=3&domainID=1"
            )/*,
			'dynamicMenu' => array(
                "name"  => "תפריט דינמי",
                "href"  => "menus/menuAutoComp/index.php?langID=1&menuType=50"
            )*/
		)
    ),

	array(
        "name"  => "ניהול מיניסייטים",
        "href"  => "",
        "icon"  => "/images/content.png",
        "level" => "10",
        "sub"   => array(
			'miniSites' => array(
                "name"  => "מיניסייטים",
                "href"  => "moduls/minisites/table.php"
            ),
			'unitsSab' => array(
                "name"  => "סוגי יחידות",
                "href"  => "moduls/minisites/rooms/roomTypes.php"
            ),
			'siteReviews' => array(
                "name"  => "חוות דעת",
                "href"  => "moduls/minisites/allReviews/index.php"
			),
			'ag_agreements' => array(
                "name"  => "הסכמי פרסום",
                "href"  => "moduls/minisites/ag_agreements/index.php"
			),
			'facilities' => array(
                "name"  => "מאפיינים",
                "href"  => "moduls/facilities/index.php"
            ),
			'manageAccessories' => array(
                "name"  => "ניהול אביזרים",
                "href"  => "moduls/minisites/accessoires/index.php"
			),
			'managePeriods' => array(
                "name"  => "תקופות חמות",
                "href"  => "moduls/minisites/hot_periods/index.php"
			),
            'manageNotPeriods' => array(
                "name"  => "חגים ביומן",
                "href"  => "moduls/minisites/cool_periods/index.php"
            ),
			'manageSapces' => array(
                "name"  => "ניהול חדרים",
                "href"  => "moduls/minisites/spaces/index.php"
			),
            'bizUsers' => array(
                "name"  => "משתמשים Bizonline",
                "href"  => "moduls/minisites/biz_users/index.php"
            ),
			'manageOrders' => array(
                "name"  => "ניהול הזמנות",
                "href"  => "moduls/minisites/orders/index.php"
            ),
			'statistics' => array(
                "name"  => "סטטיסטיקות",
                "href"  => "moduls/minisites/stats/index.php"
            ),
            'relSite' => array(
                "name"  => "שיוך אתרים",
                "href"  => "moduls/minisites/relSites.php"
            ),
            'siteDomains' => array(
                "name"  => "שיוך לדומיינים",
                "href"  => "moduls/minisites/sitesDomains/table.php"
            ),
            /*'promotedDomains' => array(
                "name"  => "מקומות חמים",
                "href"  => "moduls/minisites/promotedDomains/table.php"
            ),*/
            'promotedDomains' => array(
                    "name"  => "קידום ושיוך אתרים",
                    "href"  => "moduls/minisites/sitesDomains/table.php"
            ),
            'paycupons' => array(
                "name"  => "ניהול ספקים",
                "href"  => "moduls/minisites/arriveSource/table.php"
            ),
            'sources' => array(
                "name"  => "ניהול מקורות הגעה",
                "href"  => "moduls/minisites/arriveSource/sources.php"
            ),
            'movement' => array(
                "name"  => "שיוך ל-MOVE",
                "href"  => "moduls/minisites/move/table.php"
            )
			/*'writers' => array(
                "name"  => "כותבי",
                "href"  => "moduls/minisites/reviewWriters/index.php"
            ),
			'manageJump' => array(
                "name"  => "ניהול הקפצות",
                "href"  => ""
            )*/
		)
    ),
	array(
	    "name" =>"דוחות הזמנות",
        "href" =>"",
        "icon" => "/images/content.png",
        "level"=>"10",
        "sub" => array(
            'giftCards' => array(
                "name"  => "מימוש שוברים",
                "href"  => "moduls/minisites/giftCards/index.php"
            ),
            'giftCardsReport' => array(
                "name"  => "דוחות שוברים",
                "href"  => "moduls/minisites/giftCards/report.php"
            ),
            'giftCards2' => array(
                "name"  => "מימוש שוברים ישירים",
                "href"  => "moduls/minisites/giftCards/index_direct.php"
            ),
            'giftCardsReport2' => array(
                "name"  => "דוחות שוברים ישירים",
                "href"  => "moduls/minisites/giftCards/report_direct.php"
            ),
            'spaOrders' => array(
                "name"  => "הזמנות אונליין בתי ספא",
                "href"  => "moduls/minisites/orders/onlines.php"
            )
        )
    ),
	array(
       "name"  => "תכנים",
        "href"  => "",
        "icon"  => "/images/content.png",
        "level" => "10",
        "sub"   => array(
			'contentPages' => array(
                "name"  => "עמודי תוכן",
                "href"  => "moduls/pages/contentPages/index.php?type=1"
            ),
			'contentStaticPages' => array(
                "name"  => "סיבות הגעה",
                "href"  => "moduls/pages/staticPages/index.php?type=100"
            ),
			'contentStaticPages2' => array(
                "name"  => "תוכן סטטי",
                "href"  => "moduls/pages/staticPages/index.php?type=101"
            ),
			'faqs' => array(
                "name"  => "שאלות ותשובות",
                "href"  => "moduls/pages/staticPages/index.php?type=165"
            ),
			'faqsnew' => array(
                "name"  => "שאלות ותשובות חדש",
                "href"  => "moduls/faqs/index.php"
            ),
			'agreement' => array(
                "name"  => "עריכת הסכם ראשי",
                "href"  => "moduls/pages/agreement/index.php"
            ),
			'bizAgreement' => array(
                "name"  => "הסכמי מערכת",
                "href"  => "moduls/pages/contentPages/index.php?type=2"
            ),
            'defaultHealthTexts' => array(
                "name"  => "הצהרות ברירת מחדל",
                "href"  => "moduls/pages/staticPages/index.php?type=105"
            ),
			'healthQuestions' => array(
                "name"  => "שאלות ה. קורונה",
                "href"  => "moduls/pages/staticPages/index.php?type=106"
            ),
			'healthQuestions2' => array(
                "name"  => "שאלות ה. בריאות",
                "href"  => "moduls/pages/staticPages/index.php?type=107"
            ),
			'attractions' => array(
                "name"  => "אטרקציות",
                "href"  => "moduls/attractions/table.php"
            ),
			'amana' => array(
                "name"  => "אמנת שירות",
                "href"  => "/moduls/pages/staticPages/index.php?type=103"
            ),
			'restaurants' => array(
                "name"  => "מסעדות",
                "href"  => "moduls/restaurants/table.php"
            )

		)
    ),
	array(
        "name"  => "כתבות",
        "href"  => "",
        "icon"  => "/images/content.png",
        "level" => "10",
        "sub"   => array(
			'categoriesArt' => array(
                "name"  => "קטגוריות",
                "href"  => "menus/index.php?menuType=5&langID=1"
            ),
			'catPages' => array(
                "name"  => "דפי קטגוריות",
                "href"  => "moduls/articles/categoryPage/index.php"
            ),
			'babbels' => array(
                "name"  => "כתבות",
                "href"  => "moduls/articles/index.php?langID=1&domainID=1"
            )
		)
    ),
	'dictionary' => array(
        "name"  => "מילון שפות",
        "href"  => "dictionary.php",
        "icon"  => "/images/prop.png",
        "level" => "10",
        "sub"   => array()
    ),


    'access' => array(
        "name"  => "מערכת הרשאות",
        "href"  => "access/index.php",
        "icon"  => "/images/prop.png",
        "level" => "10",
        "sub"   => array()
    ),
    'settings' => array(
        "name"  => "הגדרות",
        "href"  => "configurations.php",
        "icon"  => "/images/prop.png",
        "level" => "10",
        "sub"   => array()
    ),
    'homepage_settings' => array(
        "name"  => "הגדרות דף הבית",
        "href"  => "homepage.php?domainID=1",
        "icon"  => "/images/prop.png",
        "level" => "10",
        "sub"   => array()
    ) ,
    'spa_setting' => array(
        "name"  => "הגדרות בתי ספא",
        "href"  => "",
        "icon"  => "/images/prop.png",
        "level" => "10",
        "sub"   => array(
            "treatments" => array(
                "name" => "טיפולי ספא",
                "href" => "moduls/treatments/treatmentsList/"
            ),
            "location_characteristics" => array(
                "name" => "מאפייני המקום",
                "href" => "moduls/spa_setting/location_characteristics/"
            )
        )
)
);