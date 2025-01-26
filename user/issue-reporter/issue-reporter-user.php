<?php
// issue-reporter-user.php
// ============ USER INTERFACE (Widget) API ===============

// התחלת בופר יציאה ללכידת כל יציאה בלתי צפויה
ob_start();

// כיבוי הצגת שגיאות ללקוח והפנייתם ללוג
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/issue-reporter.log');

error_reporting(E_ALL);

// הגדרת סוג התוכן כ-JSON
header('Content-Type: application/json; charset=utf-8');

// הגדרת נתיבים
$issuesFile = __DIR__ . '/issues.json';
$uploadsDir = __DIR__ . '/issues-uploads';
$logFile = __DIR__ . '/issue-reporter.log';

// ודא שקובץ issues.json קיים
if (!file_exists($issuesFile)) {
    file_put_contents($issuesFile, json_encode([]));
}

// ודא שתיקיית ההעלאות קיימת
if (!is_dir($uploadsDir)) {
    mkdir($uploadsDir, 0777, true);
}

$maxImages = 4;
$maxFileSize = 5 * 1024 * 1024; // 5MB

function logMessage($message)
{
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    //file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

function loadIssues()
{
    global $issuesFile;
    $fp = fopen($issuesFile, 'r');
    if ($fp === false) {
        logMessage("Failed to open issues file for reading.");
        return [];
    }
    if (flock($fp, LOCK_SH)) {
        $size = filesize($issuesFile);
        $size = $size > 0 ? $size : 1;
        $data = fread($fp, $size);
        flock($fp, LOCK_UN);
        fclose($fp);
        $arr = json_decode($data, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            logMessage("JSON decode error: " . json_last_error_msg());
            return [];
        }
        return is_array($arr) ? $arr : [];
    } else {
        logMessage("Failed to acquire shared lock for reading issues.");
        fclose($fp);
        return [];
    }
}

function saveIssues($arr)
{
    global $issuesFile;
    $fp = fopen($issuesFile, 'w');
    if ($fp === false) {
        logMessage("Failed to open issues file for writing.");
        return false;
    }
    if (flock($fp, LOCK_EX)) {
        $json = json_encode($arr, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            logMessage("JSON encode error: " . json_last_error_msg());
            flock($fp, LOCK_UN);
            fclose($fp);
            return false;
        }
        fwrite($fp, $json);
        fflush($fp);
        flock($fp, LOCK_UN);
        fclose($fp);
        return true;
    } else {
        logMessage("Failed to acquire exclusive lock for writing issues.");
        fclose($fp);
        return false;
    }
}

function &findIssueById(&$issues, $id)
{
    foreach ($issues as &$x) {
        if ($x['id'] == $id) {
            return $x;
        }
    }
    $null = null;
    return $null;
}

function generateNextID()
{
    // Use current timestamp for a unique numeric ID
    return time();
}

function handleUploads($files, $existingCount = 0)
{
    global $uploadsDir, $maxImages, $maxFileSize;
    if (!isset($files['fileInput'])) return [];

    $spaceLeft = $maxImages - $existingCount;
    if ($spaceLeft <= 0) return [];

    $uploaded = [];
    foreach ($files['fileInput']['name'] as $i => $fname) {
        if (count($uploaded) >= $spaceLeft) break;
        $tmp = $files['fileInput']['tmp_name'][$i] ?? '';
        $error = $files['fileInput']['error'][$i] ?? UPLOAD_ERR_NO_FILE;
        $size = $files['fileInput']['size'][$i] ?? 0;

        if ($error !== UPLOAD_ERR_OK) {
            logMessage("Upload error for file '$fname': " . uploadErrorMessage($error));
            continue;
        }
        if ($size > $maxFileSize) {
            logMessage("File '$fname' exceeds maximum size of $maxFileSize bytes.");
            continue;
        }
        if ($fname && is_uploaded_file($tmp)) {
            $safe = generateSafeFilename($fname);
            $filePath = $uploadsDir . '/' . $safe;
            if (@move_uploaded_file($tmp, $filePath)) {
                // Validate mime
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $filePath);
                finfo_close($finfo);
                if (strpos($mime, 'image/') !== 0) {
                    unlink($filePath);
                    logMessage("File '$fname' rejected due to invalid MIME type: $mime");
                    continue;
                }
                $uploaded[] = $safe;
                logMessage("Uploaded file: $safe");
            } else {
                logMessage("Failed to upload file: $fname");
            }
        }
    }
    return $uploaded;
}

function generateSafeFilename($orig)
{
    $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];
    if (!in_array($ext, $allowed)) $ext = 'jpg';
    return bin2hex(random_bytes(16)) . '.' . $ext;
}

function addComment(&$issue, $text, $author)
{
    $issue['comments'] ??= [];
    $issue['comments'][] = [
        'text' => trim($text),
        'timestamp' => date('Y-m-d H:i:s'),
        'author' => $author
    ];
    logMessage("Added comment by $author: $text");
}

function editComment(&$issue, $index, $newText)
{
    if (isset($issue['comments'][$index])) {
        $oldText = $issue['comments'][$index]['text'];
        $issue['comments'][$index]['text'] = trim($newText);
        $issue['comments'][$index]['timestamp'] = date('Y-m-d H:i:s');
        logMessage("Edited comment index $index: '$oldText' to '$newText'");
    }
}

function deleteComment(&$issue, $index)
{
    if (isset($issue['comments'][$index])) {
        $deleted = $issue['comments'][$index]['text'];
        array_splice($issue['comments'], $index, 1);
        logMessage("Deleted comment index $index: '$deleted'");
    }
}

function uploadErrorMessage($error_code)
{
    $errors = [
        UPLOAD_ERR_INI_SIZE   => 'The uploaded file exceeds the upload_max_filesize directive.',
        UPLOAD_ERR_FORM_SIZE  => 'The uploaded file exceeds the MAX_FILE_SIZE directive.',
        UPLOAD_ERR_PARTIAL    => 'The uploaded file was only partially uploaded.',
        UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
        UPLOAD_ERR_EXTENSION  => 'A PHP extension stopped the file upload.',
    ];
    return $errors[$error_code] ?? 'Unknown upload error.';
}

function validateIssueSchema($issue)
{
    // Basic validation
    $required = ["id", "issue_type", "username", "problem", "status", "date_reported", "comments"];
    foreach ($required as $field) {
        if (!isset($issue[$field])) {
            logMessage("Validation failed: Missing field '$field'.");
            return false;
        }
    }
    return true;
}

// Exception / Error handlers
set_exception_handler(function ($e) {
    logMessage("Uncaught exception: " . $e->getMessage());
    echo json_encode(["success" => false, "error" => "Internal server error"], JSON_UNESCAPED_UNICODE);
    ob_end_flush();
    exit;
});

set_error_handler(function ($severity, $message, $file, $line) {
    logMessage("Error: $message in $file on line $line");
    echo json_encode(["success" => false, "error" => "Internal server error"], JSON_UNESCAPED_UNICODE);
    ob_end_flush();
    exit;
});

logMessage("Incoming request: " . $_SERVER['REQUEST_METHOD'] . " " . $_SERVER['REQUEST_URI']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sanitizedPost = array_map(function($value) {
        return is_array($value) ? json_encode($value) : $value;
    }, $_POST);
    logMessage("POST data: " . json_encode($sanitizedPost));
    if (isset($_FILES['fileInput'])) {
        logMessage("Uploaded files: " . json_encode($_FILES['fileInput']['name']));
    }
}

// 1) Fetch issues for user (GET ?json=1&username=...)
if (isset($_GET['json']) && $_GET['json'] === '1' && !empty($_GET['username'])) {
    $username = trim($_GET['username']);
    $all = loadIssues();
    $filtered = array_filter($all, function ($row) use ($username) {
        return $row['username'] === $username;
    });
    echo json_encode(array_values($filtered), JSON_UNESCAPED_UNICODE);
    logMessage("Returned " . count($filtered) . " issues for user '$username'");
    ob_end_flush();
    exit;
}

// 2) Create a new issue
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_user_issue'])) {
    $username = trim($_POST['username'] ?? '');
    $issue_type = trim($_POST['issue_type'] ?? '');
    $problem = trim($_POST['problem'] ?? '');
    $desc = trim($_POST['description'] ?? '');

    $missingFields = [];
    if ($username === '') $missingFields[] = 'username';
    if ($issue_type === '') $missingFields[] = 'issue_type';
    if ($problem === '') $missingFields[] = 'problem';

    if (!empty($missingFields)) {
        $error = "Missing fields: " . implode(', ', $missingFields);
        echo json_encode(["success" => false, "error" => $error], JSON_UNESCAPED_UNICODE);
        logMessage("Failed to create issue: $error");
        ob_end_flush();
        exit;
    }

    $all = loadIssues();
    $newId = (string)generateNextID();
    $imgs = handleUploads($_FILES, 0);

    $newIssue = [
        'id' => $newId,
        'issue_type' => $issue_type,
        'username' => $username,
        'problem' => $problem,
        'description' => $desc,
        'images' => $imgs,
        'status' => 'not started',
        'date_reported' => date('Y-m-d H:i:s'),
        'ended_at' => null,
        'comments' => [],
        'assigned_admin' => ''
    ];

    if (!validateIssueSchema($newIssue)) {
        $error = "Invalid issue data structure";
        echo json_encode(["success" => false, "error" => $error], JSON_UNESCAPED_UNICODE);
        logMessage("Failed to create issue: $error for user '$username'");
        ob_end_flush();
        exit;
    }

    $all[] = $newIssue;
    if (saveIssues($all)) {
        echo json_encode(["success" => true], JSON_UNESCAPED_UNICODE);
        logMessage("Created new issue with ID: $newId for user '$username'");
    } else {
        $error = "Failed to save issue";
        echo json_encode(["success" => false, "error" => $error], JSON_UNESCAPED_UNICODE);
        logMessage("Failed to save issue for user '$username'");
    }
    ob_end_flush();
    exit;
}

// 3) Edit an issue (user_action=edit_user_issue)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_action']) && $_POST['user_action'] === 'edit_user_issue') {
    $username = trim($_POST['username'] ?? '');
    $issue_id = trim($_POST['issue_id'] ?? '');

    if ($issue_id === '') {
        $error = "Missing issue ID";
        echo json_encode(["success" => false, "error" => $error], JSON_UNESCAPED_UNICODE);
        logMessage("Failed to edit issue: $error");
        ob_end_flush();
        exit;
    }
    $all = loadIssues();
    $found = &findIssueById($all, $issue_id);
    if (!$found) {
        $error = "Issue not found";
        echo json_encode(["success" => false, "error" => $error], JSON_UNESCAPED_UNICODE);
        logMessage("Failed to edit issue: $error (ID: $issue_id)");
        ob_end_flush();
        exit;
    }
    // Must be the original reporter
    if ($found['username'] !== $username) {
        $error = "Unauthorized";
        echo json_encode(["success" => false, "error" => $error], JSON_UNESCAPED_UNICODE);
        logMessage("Unauthorized attempt to edit issue ID: $issue_id by user '$username'");
        ob_end_flush();
        exit;
    }

    // Possibly reopen
    if (isset($_POST['reopen']) && $found['status'] === 'done') {
        $found['status'] = 'not started';
        $found['ended_at'] = null;
        addComment($found, "Issue reopened by user.", $username);
        if (saveIssues($all)) {
            echo json_encode(["success" => true]);
            logMessage("Reopened issue ID: $issue_id by user '$username'");
        } else {
            $error = "Failed to reopen issue";
            echo json_encode(["success" => false, "error" => $error]);
            logMessage("Failed to reopen issue ID: $issue_id by user '$username'");
        }
        ob_end_flush();
        exit;
    }

    if ($found['status'] === 'done') {
        $error = "Issue is done, cannot edit unless reopened first";
        echo json_encode(["success" => false, "error" => $error]);
        logMessage("Issue is done, cannot edit unless reopened (ID: $issue_id by user '$username')");
        ob_end_flush();
        exit;
    }

    // Update fields
    $issue_type = trim($_POST['issue_type'] ?? '');
    $problem = trim($_POST['problem'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($issue_type !== '') $found['issue_type'] = $issue_type;
    if ($problem !== '') $found['problem'] = $problem;
    if ($description !== '') $found['description'] = $description;

    // Remove existing images if requested
    if (isset($_POST['removeImages'])) {
        $removeList = $_POST['removeImages'];
        foreach ($removeList as $rmName) {
            if (in_array($rmName, $found['images'])) {
                $found['images'] = array_values(array_filter($found['images'], fn($x) => $x !== $rmName));
                @unlink($uploadsDir . '/' . $rmName);
                logMessage("Removed existing image $rmName for issue $issue_id");
            }
        }
        $found['images'] = array_values($found['images']);
    }

    // Handle new file uploads
    $existingCount = isset($found['images']) ? count($found['images']) : 0;
    $newImgs = handleUploads($_FILES, $existingCount);
    if ($newImgs) {
        $found['images'] = array_merge($found['images'], $newImgs);
    }

    if (saveIssues($all)) {
        echo json_encode(["success" => true]);
        logMessage("Edited issue ID: $issue_id by user '$username'");
    } else {
        $error = "Failed to save issue";
        echo json_encode(["success" => false, "error" => $error]);
        logMessage("Failed to save edited issue ID: $issue_id by user '$username'");
    }
    ob_end_flush();
    exit;
}

// 4) Delete (close) an issue
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_action']) && $_POST['user_action'] === 'delete_issue_user') {
    $username = trim($_POST['username'] ?? '');
    $issue_id = trim($_POST['issue_id'] ?? '');

    if ($issue_id === '') {
        $error = "Missing issue ID";
        echo json_encode(["success" => false, "error" => $error]);
        logMessage("Failed to close issue: $error");
        ob_end_flush();
        exit;
    }
    $all = loadIssues();
    $found = &findIssueById($all, $issue_id);
    if (!$found) {
        $error = "Issue not found";
        echo json_encode(["success" => false, "error" => $error]);
        logMessage("Failed to close issue: $error (ID: $issue_id)");
        ob_end_flush();
        exit;
    }
    if ($found['username'] !== $username) {
        $error = "Unauthorized";
        echo json_encode(["success" => false, "error" => $error]);
        logMessage("Unauthorized attempt to close issue ID: $issue_id by user '$username'");
        ob_end_flush();
        exit;
    }
    $found['status'] = 'done';
    $found['ended_at'] = date('Y-m-d H:i:s');
    addComment($found, "Issue closed by user.", $username);
    if (saveIssues($all)) {
        echo json_encode(["success" => true]);
        logMessage("Closed issue ID: $issue_id by user '$username'");
    } else {
        $error = "Failed to close issue";
        echo json_encode(["success" => false, "error" => $error]);
        logMessage("Failed to close issue ID: $issue_id by user '$username'");
    }
    ob_end_flush();
    exit;
}

// 5) Add comment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_comment'])) {
    $issue_id = trim($_POST['issue_id'] ?? '');
    $commentText = trim($_POST['comment'] ?? '');
    $username = trim($_POST['username'] ?? '');

    if (!$issue_id || !$commentText || !$username) {
        $error = "Missing data";
        echo json_encode(["success" => false, "error" => $error]);
        logMessage("Failed to add comment: $error");
        ob_end_flush();
        exit;
    }
    $all = loadIssues();
    $found = &findIssueById($all, $issue_id);
    if (!$found) {
        $error = "Issue not found";
        echo json_encode(["success" => false, "error" => $error]);
        logMessage("Failed to add comment: $error (ID: $issue_id)");
        ob_end_flush();
        exit;
    }
    if ($found['status'] === 'done') {
        $error = "Issue is done, cannot add comment";
        echo json_encode(["success" => false, "error" => $error]);
        logMessage("Attempt to add comment to done issue ID: $issue_id by user '$username'");
        ob_end_flush();
        exit;
    }
    if ($found['username'] !== $username) {
        $error = "Unauthorized to add comment (not your issue)";
        echo json_encode(["success" => false, "error" => $error]);
        logMessage("Unauthorized attempt to add comment to issue ID: $issue_id by user '$username'");
        ob_end_flush();
        exit;
    }
    addComment($found, $commentText, $username);
    if (saveIssues($all)) {
        echo json_encode(["success" => true]);
        logMessage("Added comment to issue ID: $issue_id by user '$username'");
    } else {
        $error = "Failed to add comment";
        echo json_encode(["success" => false, "error" => $error]);
        logMessage("Failed to add comment to issue ID: $issue_id by user '$username'");
    }
    ob_end_flush();
    exit;
}

// 6) Edit comment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_comment'])) {
    $issue_id = trim($_POST['issue_id'] ?? '');
    $cIndex = trim($_POST['comment_index'] ?? '');
    $newText = trim($_POST['comment_text'] ?? '');
    $username = trim($_POST['username'] ?? '');

    if (!$issue_id || $cIndex === '' || !$newText || !$username) {
        $error = "Missing data";
        echo json_encode(["success" => false, "error" => $error]);
        logMessage("Failed to edit comment: $error");
        ob_end_flush();
        exit;
    }
    $all = loadIssues();
    $found = &findIssueById($all, $issue_id);
    if (!$found) {
        $error = "Issue not found";
        echo json_encode(["success" => false, "error" => $error]);
        logMessage("Failed to edit comment: $error (ID: $issue_id)");
        ob_end_flush();
        exit;
    }
    if ($found['status'] === 'done') {
        $error = "Issue is done, cannot edit comments";
        echo json_encode(["success" => false, "error" => $error]);
        logMessage("Attempt to edit comment on done issue ID: $issue_id by user '$username'");
        ob_end_flush();
        exit;
    }
    if (!isset($found['comments'][$cIndex])) {
        $error = "Comment not found";
        echo json_encode(["success" => false, "error" => $error]);
        logMessage("Failed to edit comment: $error (ID: $issue_id, Comment Index: $cIndex)");
        ob_end_flush();
        exit;
    }
    // Must be this user's own comment
    if ($found['comments'][$cIndex]['author'] !== $username) {
        $error = "Unauthorized to edit this comment";
        echo json_encode(["success" => false, "error" => $error]);
        logMessage("Unauthorized attempt to edit comment on issue ID: $issue_id by user '$username'");
        ob_end_flush();
        exit;
    }
    editComment($found, $cIndex, $newText);
    if (saveIssues($all)) {
        echo json_encode(["success" => true]);
        logMessage("Edited comment on issue ID: $issue_id by user '$username'");
    } else {
        $error = "Failed to edit comment";
        echo json_encode(["success" => false, "error" => $error]);
        logMessage("Failed to edit comment on issue ID: $issue_id by user '$username'");
    }
    ob_end_flush();
    exit;
}

// 7) Delete comment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_comment'])) {
    $issue_id = trim($_POST['issue_id'] ?? '');
    $cIndex = trim($_POST['comment_index'] ?? '');
    $username = trim($_POST['username'] ?? '');

    if (!$issue_id || $cIndex === '' || !$username) {
        $error = "Missing data";
        echo json_encode(["success" => false, "error" => $error]);
        logMessage("Failed to delete comment: $error");
        ob_end_flush();
        exit;
    }
    $all = loadIssues();
    $found = &findIssueById($all, $issue_id);
    if (!$found) {
        $error = "Issue not found";
        echo json_encode(["success" => false, "error" => $error]);
        logMessage("Failed to delete comment: $error (ID: $issue_id)");
        ob_end_flush();
        exit;
    }
    if ($found['status'] === 'done') {
        $error = "Issue is done, cannot delete comments";
        echo json_encode(["success" => false, "error" => $error]);
        logMessage("Attempt to delete comment on done issue ID: $issue_id by user '$username'");
        ob_end_flush();
        exit;
    }
    if (!isset($found['comments'][$cIndex])) {
        $error = "Comment not found";
        echo json_encode(["success" => false, "error" => $error]);
        logMessage("Failed to delete comment: $error (ID: $issue_id, Comment Index: $cIndex)");
        ob_end_flush();
        exit;
    }
    // Must be this user's own comment
    if ($found['comments'][$cIndex]['author'] !== $username) {
        $error = "Unauthorized to delete this comment";
        echo json_encode(["success" => false, "error" => $error]);
        logMessage("Unauthorized attempt to delete comment on issue ID: $issue_id by user '$username'");
        ob_end_flush();
        exit;
    }
    deleteComment($found, $cIndex);
    if (saveIssues($all)) {
        echo json_encode(["success" => true]);
        logMessage("Deleted comment on issue ID: $issue_id by user '$username'");
    } else {
        $error = "Failed to delete comment";
        echo json_encode(["success" => false, "error" => $error]);
        logMessage("Failed to delete comment on issue ID: $issue_id by user '$username'");
    }
    ob_end_flush();
    exit;
}

// 8) No matching route
echo json_encode(["success" => false, "error" => "Nothing matched"]);
logMessage("No matching route for the request.");
ob_end_flush();
exit;
