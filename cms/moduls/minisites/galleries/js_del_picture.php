<?php
include_once "../../../bin/system.php";

$picID = intval($_POST['picID']);

$fileName = udb::single_value("SELECT `src` FROM `files` WHERE `id` = " . $picID);
if (!$fileName)
    exit;

$galPath = '../../../../gallery/';
$basename = basename($fileName);

if (is_file($galPath . $basename)){
    foreach (new DirectoryIterator($galPath . 'thumb') as $file) {
        if ($file->isDir()){
            $subpic = rtrim($file->getPathname(), '/') . '/' . $basename;

            if (is_file($subpic))
                unlink($subpic);
        }
    }

    unlink($galPath . $basename);
}

udb::query("DELETE FROM `files` WHERE `id` = " . $picID);
udb::query("DELETE FROM `files_text` WHERE `file_id` = " . $picID);
udb::query("DELETE pictures.*, pictures_text.* FROM `pictures` LEFT JOIN `pictures_text` USING(`pictureID`) WHERE pictures.fileID = " . $picID);
