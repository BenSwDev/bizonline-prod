<div class="cc">
    <div class="item">
        <div class="title">1. הקימו ספק בתנאים בלעדיים</div>
        <!-- <ul>
            <li>פשוט וקל ללא עלות הקמה</li>
            <li>עמלות סליקה נמוכות ההצעה הטובה ביותר*</li>
            <li>קבלו מערכת הנהלת חשבונות מתקדמת</li>
            <li>אפשרות לשמור כרטיס ולחייב בהמשך</li>
            <li>מערכת אמינה מבית MAX</li>
        </ul> -->
        <?/*
		<a class="join" style="text-decoration:none;" 
		href_old="https://businesslc.max.co.il/landingpages/dobsimplepaymasof?externalSource=57&externalId=<?=SITE_ID?>&utm_source=bizportal&utm_medium=link&utm_campaign=sp_bizportal" 
		 //OLD URL?>
		href="https://businesslc.max.co.il/join/wizard/personal-details?productType=137&externalID=<?=SITE_ID?>&ExternalSource=57&utm_source=bizportal&utm_medium=link&utm_campaign=sp_bizportal"
		target="_blank">לחצו להצטרפות</a>*/?>
		<div class="join" onclick="$('#ccpop').fadeIn('fast');$('#typecc').val('1');$('#ccpop_title').removeClass('cc2')">לחצו להצטרפות</div>
    </div>
    <div class="item">
        <div class="title">2. יש לי כבר ספק סליקה ואני מעוניין לסלוק דרכו</div>
        <!-- <ul>
            <li>אין בעיה, מלאו את פרטי המסוף ואנחנו נחבר אותו</li>
            <li>פשוט וקל וללא עלות הקמה</li>
            <li>עמלות סליקה נמוכות ההצעה הטובה ביותר*</li>
            <li>קבלו מערכת הנהלת חשבונות מתקדמת</li>
            <li>אפשרות לשמור כרטיס ולחייב בהמשך</li>
        </ul> -->
        <div class="join" onclick="$('#ccpop').fadeIn('fast');$('#typecc').val('2');$('#ccpop_title').addClass('cc2')">לחצו לחיבור המסוף</div>
    </div>
    <div class="call">מתלבטים באיזה מסלול לבחור? <div>יש לכם שאלה?</div><div>דברו איתנו</div><div><a href="tel:053-710-6102">053-710-6102</a></div></div>
    <div class="ccpop" id="ccpop" style="display:none">
        <div class="container">
            <div class="close" onclick="closeCCDiv('#ccpop')"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 21 21" width="21" height="21"><path class="shp0" d="M1.3 1.3C1.8 0.9 2.5 0.9 2.9 1.3L11 9.4 19.1 1.3C19.5 0.9 20.2 0.9 20.7 1.3 21.1 1.8 21.1 2.5 20.7 2.9L12.6 11 20.7 19.1C21.1 19.5 21.1 20.2 20.7 20.7 20.4 20.9 20.2 21 19.9 21 19.6 21 19.3 20.9 19.1 20.7L11 12.6 2.9 20.7C2.7 20.9 2.4 21 2.1 21 1.8 21 1.5 20.9 1.3 20.7 0.9 20.2 0.9 19.5 1.3 19.1L9.4 11 1.3 2.9C0.9 2.5 0.9 1.8 1.3 1.3Z"></path></svg></div>
            <div id="ccpop_title">
				<span>הקימו ספק בתנאים בלעדיים</span>
				<span>יש לי כבר ספק סליקה ואני מעוניין לסלוק דרכו</span>
            </div>
			<form method="post" id="ccform" action="">
                <input type="hidden" name="typecc" value="" id="typecc">
				<div class="inputWrap">
                    <input type="text" name="fullName" value="<?=$siteData['owners']?>"<?=$siteData['owners']?' class="not-empty"':""?>>
                    <label for="fullName">שם מלא</label>
                </div>
                <div class="inputWrap">
                    <input type="text" name="siteName" value="<?=$siteData['siteName']?>"<?=$siteData['siteName']?' class="not-empty"':""?>>
                    <label for="siteName">שם המתחם</label>
                </div>
                <div class="inputWrap">
                    <input type="tel" name="phone" value="<?=$siteData['phone']?>"<?=$siteData['phone']?' class="not-empty"':""?>>
                    <label for="phone">טלפון</label>
                </div>
                <div class="inputWrap">
                    <input type="text" name="email" value="<?=$siteData['email']?>"<?=$siteData['email']?' class="not-empty"':""?>>
                    <label for="email">אימייל</label>
                </div>
               <div class="checkbox">
                    <input type="checkbox" id="invoice" name="invoice">
                    <label for="invoice">ברצוני להנפיק חשבוניות דרך המערכת</label>
                </div>
                <div class="submit" onclick="sendContact('ccform')">שלח</div>
            </form>
        </div>
    </div>
</div>

<style>
#ccpop_title{font-weight:bold;font-size:22px;text-align:center;margin-top:30px;padding:0 30px}
#ccpop_title:not(.cc2) span:nth-child(2){display:none}
#ccpop_title.cc2 span:nth-child(1){display:none}
</style>