<?php
/**
 * copy.php
 *
 * מציג את תוכן כל קבצי ה-PHP, JS, CSS, ו-JSON בתיקייה זו (מלבד copy.php)
 * כטקסט אחד רציף, כך שתוכלו להעתיק הכל בבת אחת.
 */

// נגדיר אילו סיומות קבצים אנחנו רוצים להציג ובאיזה סדר
$extensionsOrder = ['php', 'js', 'css', 'json'];

// סורקים את התיקייה
$files = scandir(__DIR__);

// ניצור משתנה שבו נאחסן את כל התוכן
$allContent = "";

// עבור כל סיומת בסדר המוגדר
foreach ($extensionsOrder as $ext) {
    // נעבור על רשימת הקבצים
    foreach ($files as $file) {
        // אם זה תיקייה או שזה copy.php עצמו, נדלג
        if (
            is_dir($file) ||
            $file === basename(__FILE__)
        ) {
            continue;
        }

        // אם ההרחבה של הקובץ היא ההרחבה הרלוונטית
        if (pathinfo($file, PATHINFO_EXTENSION) === $ext) {
            // נטען את התוכן
            $content = file_get_contents($file);
            if ($content === false) {
                // במקרה של כישלון בקריאה, נדלג
                continue;
            }
            
            // נוסיף כותרת וגבולות, כדי שיהיה מסודר
            $allContent .= "========================================\n";
            $allContent .= "File: $file\n";
            $allContent .= "========================================\n";
            $allContent .= $content . "\n";
            $allContent .= "========== END FILE: $file ==========\n\n\n";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="he">
<head>
    <meta charset="UTF-8">
    <title>Project Files Content</title>
    <style>
        body {
            margin: 0; 
            padding: 0;
        }
        pre {
            margin: 10px;
            font-family: Consolas, monospace;
            white-space: pre-wrap; /* עוטף שורות ארוכות */
        }
    </style>
</head>
<body>
<pre>
<?php 
// נשתמש ב-htmlspecialchars כדי למנוע תקלות תצוגה
echo htmlspecialchars($allContent, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); 
?>
</pre>
</body>
</html>
