<?php
// TranslationsUI.php

// Increase memory limit if needed
ini_set('memory_limit', '131072M'); // Increased to handle larger files

// Start session and set error reporting
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Path to the translations.json file
$translations_file = '/home/bizdevcs/public_html/user/translations.json';

// Check if translations.json exists
if (!file_exists($translations_file)) {
    die('Error: translations.json file does not exist.');
}

// Read and decode the JSON translations
$json_content = file_get_contents($translations_file);
if ($json_content === false) {
    die('Error: Unable to read translations.json.');
}

// Ensure content is UTF-8 encoded
if (!mb_check_encoding($json_content, 'UTF-8')) {
    // Attempt to detect and convert encoding
    $detected_encoding = mb_detect_encoding($json_content, ['UTF-8', 'Windows-1255', 'ISO-8859-8'], true);
    if ($detected_encoding !== false) {
        $json_content = mb_convert_encoding($json_content, 'UTF-8', $detected_encoding);
    } else {
        die('Error: Unable to detect encoding of translations.json.');
    }
}

// Decode JSON
$translations = json_decode($json_content, true);
if ($translations === null) {
    die('Error: Unable to parse translations.json: ' . json_last_error_msg());
}

// Function to get all files with specified extensions
function getFiles($dir, $extensions = ['php', 'css', 'js']) { // Modified to include 'js'
    $files = [];
    if (!is_dir($dir)) {
        return $files;
    }

    $dir_iterator = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);
    $iterator = new RecursiveIteratorIterator($dir_iterator);

    foreach ($iterator as $file) {
        $filename = strtolower($file->getFilename());
        foreach ($extensions as $ext) {
            if (substr($filename, -strlen($ext)) === $ext) {
                $files[] = $file->getPathname();
                break;
            }
        }
    }
    return $files;
}

// Function to extract pure Hebrew phrases from a given string
function extractHebrewPhrases($text) {
    $hebrew_phrases = [];
    $pattern = '/[\p{Hebrew}]+(?:[\p{Hebrew}\s\p{P}]+)*/u';
    preg_match_all($pattern, $text, $matches);

    foreach ($matches[0] as $phrase) {
        $phrase = trim($phrase);

        if (mb_strlen($phrase, 'UTF-8') > 500 || mb_strlen($phrase, 'UTF-8') < 1) {
            continue;
        }

        if (preg_match('/[<>{};$=]/', $phrase)) {
            continue;
        }

        if (preg_match('/[\p{Hebrew}]/u', $phrase)) {
            $hebrew_phrases[] = $phrase;
        }
    }

    return $hebrew_phrases;
}

// Function to extract Hebrew strings from HTML content
function extractHebrewFromHTML($content) {
    $hebrewStrings = [];

    if (trim($content) === '') {
        return $hebrewStrings; // Return empty array if content is empty
    }

    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $content = normalizeMultilineContent('<!DOCTYPE html><html><body>' . $content . '</body></html>');
    $dom->loadHTML($content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();

    $xpath = new DOMXPath($dom);
    $textNodes = $xpath->query('//text()[not(ancestor::script or ancestor::style)]');

    foreach ($textNodes as $node) {
        $text = trim($node->nodeValue);
        if ($text === '') continue;

        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $phrases = extractHebrewPhrases($text);
        $hebrewStrings = array_merge($hebrewStrings, $phrases);
    }

    $nodes = $xpath->query('//*');
    foreach ($nodes as $node) {
        $hebrewStrings = array_merge($hebrewStrings, extractHebrewFromAttributes($node));
    }

    return $hebrewStrings;
}


// Function to extract Hebrew strings from PHP content
function extractHebrewFromPHP($content) {
    $hebrew_strings = [];

    $tokens = token_get_all($content);
    $html_content = '';
    $php_strings = [];

    $is_html = true;
    $heredoc_content = '';
    $in_heredoc = false;

    foreach ($tokens as $token) {
        if (is_array($token)) {
            $token_type = $token[0];
            $token_content = $token[1];

            if ($token_type == T_INLINE_HTML) {
                $html_content .= $token_content;
            } elseif ($token_type == T_OPEN_TAG || $token_type == T_OPEN_TAG_WITH_ECHO) {
                $is_html = false;
            } elseif ($token_type == T_CLOSE_TAG) {
                $is_html = true;
            } elseif (!$is_html) {
                if ($token_type == T_START_HEREDOC) {
                    $in_heredoc = true;
                    $heredoc_content = '';
                } elseif ($token_type == T_END_HEREDOC) {
                    $in_heredoc = false;
                    $decoded_str = html_entity_decode($heredoc_content, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    $phrases = extractHebrewPhrases($decoded_str);
                    $php_strings = array_merge($php_strings, $phrases);
                } elseif ($in_heredoc) {
                    $heredoc_content .= $token_content;
                } elseif (in_array($token_type, [T_CONSTANT_ENCAPSED_STRING, T_ENCAPSED_AND_WHITESPACE, T_COMMENT, T_DOC_COMMENT])) {
                    $str = trim($token_content, '\'\"');
                    $decoded_str = stripcslashes($str);
                    $decoded_str = html_entity_decode($decoded_str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    $phrases = extractHebrewPhrases($decoded_str);
                    $php_strings = array_merge($php_strings, $phrases);
                }
            }
        } else {
            if ($is_html) {
                $html_content .= $token;
            } elseif ($in_heredoc) {
                $heredoc_content .= $token;
            }
        }
    }

    $html_phrases = extractHebrewFromHTML($html_content);
    $hebrew_strings = array_merge($php_strings, $html_phrases);

    return $hebrew_strings;
}

// Function to extract Hebrew strings from JS content
function extractHebrewFromJS($content) {
    $hebrew_strings = [];
    $pattern = '/["\'`](?:\\\\.|[^"\'`])*["\'`]/u';
    preg_match_all($pattern, $content, $matches);

    foreach ($matches[0] as $str) {
        $str = substr($str, 1, -1);
        $str = stripcslashes($str);
        $str = html_entity_decode($str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $phrases = extractHebrewPhrases($str);
        $hebrew_strings = array_merge($hebrew_strings, $phrases);
    }

    return $hebrew_strings;
}

// Function to extract Hebrew strings from CSS content
function extractHebrewFromCSS($content) {
    $hebrew_strings = [];
    $regex_comments = '/\/\*[\s\S]*?\*\//';
    preg_match_all($regex_comments, $content, $comment_matches);

    foreach ($comment_matches[0] as $comment) {
        $decoded_comment = stripcslashes($comment);
        $decoded_comment = html_entity_decode($decoded_comment, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $phrases = extractHebrewPhrases($decoded_comment);
        $hebrew_strings = array_merge($hebrew_strings, $phrases);
    }

    $content_no_comments = preg_replace($regex_comments, '', $content);
    preg_match_all('/:\s*(["\'])(.*?)\1\s*;/u', $content_no_comments, $matches);
    foreach ($matches[2] as $match) {
        $decoded_str = stripcslashes($match);
        $decoded_str = html_entity_decode($decoded_str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $phrases = extractHebrewPhrases($decoded_str);
        $hebrew_strings = array_merge($hebrew_strings, $phrases);
    }

    preg_match_all('/(?:content|alt|title|aria-label|aria-describedby)\s*:\s*([^;]+);/u', $content_no_comments, $property_matches);
    foreach ($property_matches[1] as $match) {
        $decoded_str = stripcslashes(trim($match, '"\''));
        $decoded_str = html_entity_decode($decoded_str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $phrases = extractHebrewPhrases($decoded_str);
        $hebrew_strings = array_merge($hebrew_strings, $phrases);
    }

    return $hebrew_strings;
}


function normalizeMultilineContent($content) {
    return preg_replace('/\s+/', ' ', $content);
}

function executeJavaScriptWithPuppeteer($filePath) {
    $command = escapeshellcmd("node extractHebrewPuppeteer.js " . escapeshellarg($filePath));
    $output = shell_exec($command);

    if ($output === null) {
        return [];
    }

    $hebrewStrings = json_decode($output, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return [];
    }

    return $hebrewStrings;
}

function extractHebrewFromAttributes($node) {
    $hebrewStrings = [];
    $attributesToCheck = ['data-*', 'placeholder', 'aria-label', 'aria-describedby', 'style'];

    foreach ($attributesToCheck as $attr) {
        if ($node->hasAttribute($attr)) {
            $attrValue = $node->getAttribute($attr);
            if ($attr === 'style') {
                $hebrewStrings = array_merge($hebrewStrings, extractHebrewFromCSS($attrValue));
            } else {
                $hebrewStrings = array_merge($hebrewStrings, extractHebrewPhrases($attrValue));
            }
        }
    }

    return $hebrewStrings;
}

// Function to extract Hebrew strings based on file type
function extractHebrewFromFile($file, $content, $extension) {
    switch ($extension) {
        case 'php':
            return extractHebrewFromPHP($content);
        case 'css':
            return extractHebrewFromCSS($content);
        case 'js':
            return extractHebrewFromJS($content);
        default:
            return [];
    }
}

// Get all relevant files from specified directories
$directories = ['pages', 'partial', 'assets', 'mails', 'phpmailer', 'reports','signature','src','TCPDF'];
$hebrew_strings = [];

foreach ($directories as $subdir) {
    $full_path = __DIR__ . DIRECTORY_SEPARATOR . $subdir;
    $files = getFiles($full_path, ['php', 'css', 'js']);
    foreach ($files as $file) {
        try {
            if (basename($file) === 'translations.json') {
                continue;
            }

            $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            $content = file_get_contents($file);
            if ($content === false) {
                continue;
            }

            if (!mb_check_encoding($content, 'UTF-8')) {
                $detected_encoding = mb_detect_encoding($content, ['UTF-8', 'Windows-1255', 'ISO-8859-8'], true);
                if ($detected_encoding !== false) {
                    $content = mb_convert_encoding($content, 'UTF-8', $detected_encoding);
                } else {
                    continue;
                }
            }

            $strings_in_file = extractHebrewFromFile($file, $content, $extension);
            $hebrew_strings = array_merge($hebrew_strings, $strings_in_file);

            unset($content, $strings_in_file);
        } catch (Exception $e) {
            error_log('Error processing file ' . $file . ': ' . $e->getMessage());
            continue;
        }
    }
}

$hebrew_strings = array_unique($hebrew_strings);
$existing_hebrew_strings = array_keys($translations['en']);
$missing_strings = array_diff($hebrew_strings, $existing_hebrew_strings);

// Add missing strings to translations
if (count($missing_strings) > 0) {
    foreach ($missing_strings as $str) {
        if (!mb_check_encoding($str, 'UTF-8')) {
            $detected_encoding = mb_detect_encoding($str, ['UTF-8', 'Windows-1255', 'ISO-8859-8'], true);
            if ($detected_encoding !== false) {
                $str = mb_convert_encoding($str, 'UTF-8', $detected_encoding);
            } else {
                continue;
            }
        }
        // Initialize translations as NULL
        $translations['en'][$str] = 'NULL';
        $translations['es'][$str] = 'NULL';
        $translations['fr'][$str] = 'NULL';
        $translations['ru'][$str] = 'NULL';
    }
    saveTranslations($translations_file, $translations);
}

// Function to save translations to translations.json
function saveTranslations($translations_file, $translations) {
    array_walk_recursive($translations, function (&$item, $key) {
        if (is_string($item) && !mb_check_encoding($item, 'UTF-8')) {
            $detected_encoding = mb_detect_encoding($item, ['UTF-8', 'Windows-1255', 'ISO-8859-8'], true);
            if ($detected_encoding !== false) {
                $item = mb_convert_encoding($item, 'UTF-8', $detected_encoding);
            } else {
                $item = mb_convert_encoding($item, 'UTF-8', 'UTF-8');
            }
        }
    });

    $json_content = json_encode($translations, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

    if ($json_content === false) {
        die('Error encoding translations to JSON: ' . json_last_error_msg());
    }

    if (file_put_contents($translations_file, $json_content) === false) {
        die('Error writing to translations.json.');
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $delete_strings = $_POST['delete'] ?? [];

    foreach ($delete_strings as $encoded_hebrew => $value) {
        $hebrew = base64_decode($encoded_hebrew);
        // Remove the string from all languages
        unset($translations['en'][$hebrew]);
        unset($translations['es'][$hebrew]);
        unset($translations['fr'][$hebrew]);
        unset($translations['ru'][$hebrew]);
    }

    // Handle addition of new string
    if (isset($_POST['add_string'])) {
        $new_hebrew = trim($_POST['new_hebrew'] ?? '');
        $new_en = trim($_POST['new_en'] ?? '');
        $new_es = trim($_POST['new_es'] ?? '');
        $new_fr = trim($_POST['new_fr'] ?? '');
        $new_ru = trim($_POST['new_ru'] ?? '');

        if ($new_hebrew !== '') {
            $translations['en'][$new_hebrew] = $new_en !== '' ? $new_en : 'NULL';
            $translations['es'][$new_hebrew] = $new_es !== '' ? $new_es : 'NULL';
            $translations['fr'][$new_hebrew] = $new_fr !== '' ? $new_fr : 'NULL';
            $translations['ru'][$new_hebrew] = $new_ru !== '' ? $new_ru : 'NULL';
        }
    }

    // Process submitted translations
    $en_translations = $_POST['en'] ?? [];
    $es_translations = $_POST['es'] ?? [];
    $fr_translations = $_POST['fr'] ?? [];
    $ru_translations = $_POST['ru'] ?? [];

    // Update translations
    foreach ($en_translations as $encoded_hebrew => $english) {
        $hebrew = base64_decode($encoded_hebrew);
        $english = trim($english);
        if ($hebrew !== '') {
            $translations['en'][$hebrew] = $english !== '' ? $english : 'NULL';
        }
    }
    foreach ($es_translations as $encoded_hebrew => $spanish) {
        $hebrew = base64_decode($encoded_hebrew);
        $spanish = trim($spanish);
        if ($hebrew !== '') {
            $translations['es'][$hebrew] = $spanish !== '' ? $spanish : 'NULL';
        }
    }
    foreach ($fr_translations as $encoded_hebrew => $french) {
        $hebrew = base64_decode($encoded_hebrew);
        $french = trim($french);
        if ($hebrew !== '') {
            $translations['fr'][$hebrew] = $french !== '' ? $french : 'NULL';
        }
    }
    foreach ($ru_translations as $encoded_hebrew => $russian) {
        $hebrew = base64_decode($encoded_hebrew);
        $russian = trim($russian);
        if ($hebrew !== '') {
            $translations['ru'][$hebrew] = $russian !== '' ? $russian : 'NULL';
        }
    }

    saveTranslations($translations_file, $translations);

    // Redirect to avoid form resubmission
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Combine all Hebrew strings from translations
$all_hebrew_strings = array_unique(array_merge(
    array_keys($translations['en']),
    array_keys($translations['es']),
    array_keys($translations['fr']),
    array_keys($translations['ru'])
));

// Sort the Hebrew strings
sort($all_hebrew_strings, SORT_STRING | SORT_FLAG_CASE);

$untranslated_strings = [];
$translated_strings = [];

// Check if the string is untranslated in any language
foreach ($all_hebrew_strings as $hebrew_str) {
    $english = $translations['en'][$hebrew_str] ?? '';
    $spanish = $translations['es'][$hebrew_str] ?? '';
    $french = $translations['fr'][$hebrew_str] ?? '';
    $russian = $translations['ru'][$hebrew_str] ?? '';

    if ($english === 'NULL' || $english === '' ||
        $spanish === 'NULL' || $spanish === '' ||
        $french === 'NULL' || $french === '' ||
        $russian === 'NULL' || $russian === '') {
        $untranslated_strings[] = $hebrew_str;
    } else {
        $translated_strings[] = $hebrew_str;
    }
}

$new_words = !empty($untranslated_strings);
?>
<!DOCTYPE html>
<html lang="he">
<head>
    <meta charset="UTF-8">
    <title>ניהול תרגומים</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            direction: rtl;
            background-color: #f9f9f9;
        }
        h1 {
            text-align: center;
            color: #333;
        }
        h2 {
            margin-top: 40px;
            text-align: center;
            color: #555;
            cursor: pointer;
        }
        .message {
            background-color: #ffffcc;
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #cccc99;
            border-radius: 5px;
            text-align: center;
            color: #333;
        }
        .search-box {
            text-align: center;
            margin-bottom: 20px;
        }
        .search-box input[type="text"] {
            width: 300px;
            padding: 8px;
            font-size: 16px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        table {
            border-collapse: collapse;
            width: 100%;
            table-layout: fixed;
            margin-bottom: 20px;
            background-color: #fff;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            word-wrap: break-word;
        }
        th {
            background-color: #f2f2f2;
            color: #333;
        }
        .new-word {
            background-color: #ffe6e6;
        }
        .save-button {
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
            margin-top: 20px;
            font-size: 16px;
            border-radius: 5px;
            transition: background-color 0.3s ease;
            position: fixed;
            bottom: 20px;
            right: 20px;
        }
        .save-button:hover {
            background-color: #45a049;
        }
        input[type="text"] {
            width: 95%;
            padding: 5px;
            box-sizing: border-box;
            border: 1px solid #ccc;
            border-radius: 3px;
        }
        .collapsible {
            cursor: pointer;
            padding: 10px;
            width: 100%;
            text-align: center;
            border: none;
            outline: none;
            font-size: 18px;
        }
        .content {
            display: none;
            overflow: hidden;
        }
        .active, .collapsible:hover {
            background-color: #ccc;
        }
        .delete-checkbox {
            text-align: center;
        }
        .delete-checkbox input {
            transform: scale(1.5);
        }
        @media (max-width: 768px) {
            body {
                direction: ltr; 
            }
            .search-box input[type="text"] {
                width: 90%;
            }
        }
    </style>
    <!-- Include jQuery and DataTables -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- DataTables CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.20/css/jquery.dataTables.css">
    <!-- DataTables JS -->
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.10.20/js/jquery.dataTables.js"></script>
</head>
<body>
    <h1>ניהול תרגומים</h1>

    <?php if ($new_words): ?>
        <div class="message">ישנן מחרוזות בעברית שלא תורגמו וצריכות תרגום.</div>
    <?php endif; ?>

    <!-- Form for adding new string -->
    <h2>הוסף מחרוזת חדשה</h2>
    <form method="post" action="">
        <table>
            <thead>
                <tr>
                    <th>עברית</th>
                    <th>תרגום לאנגלית</th>
                    <th>תרגום לספרדית</th>
                    <th>תרגום לצרפתית</th>
                    <th>תרגום לרוסית</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><input type="text" name="new_hebrew" value=""></td>
                    <td><input type="text" name="new_en" value=""></td>
                    <td><input type="text" name="new_es" value=""></td>
                    <td><input type="text" name="new_fr" value=""></td>
                    <td><input type="text" name="new_ru" value=""></td>
                </tr>
            </tbody>
        </table>
        <button type="submit" name="add_string" value="1">הוסף מחרוזת</button>
    </form>

    <!-- Save button -->
    <form method="post" action="">
        <button type="submit" class="save-button">שמור תרגומים</button>

    <!-- Untranslated Strings Table -->
    <?php if (!empty($untranslated_strings)): ?>
        <h2 class="collapsible active">מחרוזות שלא תורגמו</h2>
        <div class="content" style="display: block;">
            <div class="search-box">
                <input type="text" class="searchInput" placeholder="חפש טקסט...">
            </div>
            <table id="untranslatedTable" class="display">
                <thead>
                    <tr>
                        <th>עברית</th>
                        <th>תרגום לאנגלית</th>
                        <th>תרגום לספרדית</th>
                        <th>תרגום לצרפתית</th>
                        <th>תרגום לרוסית</th>
                        <th>מחק</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    foreach ($untranslated_strings as $hebrew_str):
                        $english = $translations['en'][$hebrew_str] ?? '';
                        $spanish = $translations['es'][$hebrew_str] ?? '';
                        $french = $translations['fr'][$hebrew_str] ?? '';
                        $russian = $translations['ru'][$hebrew_str] ?? '';

                        $encoded_hebrew_str = base64_encode($hebrew_str);
                    ?>
                    <tr class="new-word">
                        <td class="hebrew-text"><?php echo htmlspecialchars($hebrew_str, ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><input type="text" name="en[<?php echo htmlspecialchars($encoded_hebrew_str, ENT_QUOTES, 'UTF-8'); ?>]" value="<?php echo ($english === 'NULL' || $english === '') ? '' : htmlspecialchars($english, ENT_QUOTES, 'UTF-8'); ?>"></td>
                        <td><input type="text" name="es[<?php echo htmlspecialchars($encoded_hebrew_str, ENT_QUOTES, 'UTF-8'); ?>]" value="<?php echo ($spanish === 'NULL' || $spanish === '') ? '' : htmlspecialchars($spanish, ENT_QUOTES, 'UTF-8'); ?>"></td>
                        <td><input type="text" name="fr[<?php echo htmlspecialchars($encoded_hebrew_str, ENT_QUOTES, 'UTF-8'); ?>]" value="<?php echo ($french === 'NULL' || $french === '') ? '' : htmlspecialchars($french, ENT_QUOTES, 'UTF-8'); ?>"></td>
                        <td><input type="text" name="ru[<?php echo htmlspecialchars($encoded_hebrew_str, ENT_QUOTES, 'UTF-8'); ?>]" value="<?php echo ($russian === 'NULL' || $russian === '') ? '' : htmlspecialchars($russian, ENT_QUOTES, 'UTF-8'); ?>"></td>
                        <td class="delete-checkbox">
                            <input type="checkbox" name="delete[<?php echo htmlspecialchars($encoded_hebrew_str, ENT_QUOTES, 'UTF-8'); ?>]">
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <!-- All Translations Table -->
    <h2 class="collapsible">כל התרגומים</h2>
    <div class="content">
        <div class="search-box">
            <input type="text" class="searchInput" placeholder="חפש טקסט...">
        </div>
        <table id="translationsTable" class="display">
            <thead>
                <tr>
                    <th>עברית</th>
                    <th>תרגום לאנגלית</th>
                    <th>תרגום לספרדית</th>
                    <th>תרגום לצרפתית</th>
                    <th>תרגום לרוסית</th>
                    <th>מחק</th>
                </tr>
            </thead>
            <tbody>
                <?php
                foreach ($translated_strings as $hebrew_str):
                    $english = $translations['en'][$hebrew_str] ?? '';
                    $spanish = $translations['es'][$hebrew_str] ?? '';
                    $french = $translations['fr'][$hebrew_str] ?? '';
                    $russian = $translations['ru'][$hebrew_str] ?? '';

                    $encoded_hebrew_str = base64_encode($hebrew_str);
                ?>
                <tr>
                    <td class="hebrew-text"><?php echo htmlspecialchars($hebrew_str, ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><input type="text" name="en[<?php echo htmlspecialchars($encoded_hebrew_str, ENT_QUOTES, 'UTF-8'); ?>]" value="<?php echo htmlspecialchars($english, ENT_QUOTES, 'UTF-8'); ?>"></td>
                    <td><input type="text" name="es[<?php echo htmlspecialchars($encoded_hebrew_str, ENT_QUOTES, 'UTF-8'); ?>]" value="<?php echo htmlspecialchars($spanish, ENT_QUOTES, 'UTF-8'); ?>"></td>
                    <td><input type="text" name="fr[<?php echo htmlspecialchars($encoded_hebrew_str, ENT_QUOTES, 'UTF-8'); ?>]" value="<?php echo htmlspecialchars($french, ENT_QUOTES, 'UTF-8'); ?>"></td>
                    <td><input type="text" name="ru[<?php echo htmlspecialchars($encoded_hebrew_str, ENT_QUOTES, 'UTF-8'); ?>]" value="<?php echo htmlspecialchars($russian, ENT_QUOTES, 'UTF-8'); ?>"></td>
                    <td class="delete-checkbox">
                        <input type="checkbox" name="delete[<?php echo htmlspecialchars($encoded_hebrew_str, ENT_QUOTES, 'UTF-8'); ?>]">
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    </form>

    <script>
        $(document).ready(function() {
            $('#untranslatedTable').DataTable({
                "pageLength": 10,
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.10.20/i18n/Hebrew.json"
                }
            });
            $('#translationsTable').DataTable({
                "pageLength": 10,
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.10.20/i18n/Hebrew.json"
                }
            });

            $(".collapsible").click(function() {
                $(this).toggleClass("active");
                $(this).next(".content").slideToggle();
            });

            $('#untranslatedTable_filter').hide();
            $('#translationsTable_filter').hide();

            $('#untranslatedTable').closest('.content').find('.searchInput').on('input', function() {
                $('#untranslatedTable').DataTable().search($(this).val()).draw();
            });

            $('#translationsTable').closest('.content').find('.searchInput').on('input', function() {
                $('#translationsTable').DataTable().search($(this).val()).draw();
            });
        });
    </script>

</body>
</html>
