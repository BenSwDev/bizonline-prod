<?php
header('Content-Type: text/plain');

$string = trim($_GET['str']);
$exact  = intval($_GET['exact']);

if (!$string)
	die('Empty search string');
if (strlen($string) < 4)
	die('Search string too short');

myread(__DIR__, $string);

function myread($start, $string)
{
    global $exact;

	$stack = array($start);
	
	while($dir = array_shift($stack)){
		$h = opendir($dir);
		if (!$h)
			echo "Can't open dir ",$dir,PHP_EOL;
		
		while (false !== ($file = readdir($h))) {
			if ($file == '.' || $file == '..')
				continue;
			elseif (is_dir($dir.'/'.$file) && in_array($file, array('webimages', 'gallery', 'upload', 'webimagesnew', 'livezilla')))
				continue;

			$sub = substr($file,-3);
				
			if (is_dir($dir.'/'.$file))
				$stack[] = $dir.'/'.$file;
			elseif (preg_match('/\.bak\d*\./i',$file))
				continue;
			elseif (!strcmp($sub,'php') || !strcmp($sub,'.js') || !strcmp($sub,'css') || !strcmp($sub,'tpl') || !strcmp($sub,'ess')){
				$text = file_get_contents($dir.'/'.$file);
                $res  = $exact ? strpos($text, $string) : stripos($text, $string);

				if ($res !== false)
					echo str_replace($start, '', $dir.'/'.$file).PHP_EOL;
			}
		}
		
		closedir($h);
	}
}

echo PHP_EOL,'Search complete.';
