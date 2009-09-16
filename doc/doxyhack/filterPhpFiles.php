<?php

include "phpDoxygenFilter.php";

if (count($argv) === 3) {
	if (($code = filterDirectory($argv[1], $argv[2]))) {
		echo "Error: " . $code . ", file: " . $argv[3]."\n";
		exit($code);
	}
}
elseif (count($argv) === 4) {
	if (($code = filterFile($argv[1], $argv[2], $argv[3]))) {
		echo "Error: " . $code . ", file: " . $argv[3]."\n";
		exit($code);
	}
}
else {
	echo "Usage: ".$argv[0]." source_directory destination_directory [filename]\n";
}

function filterDirectory($sourceDir, $destDir) {
	if (preg_match("/doc\/doxyhack/", $sourceDir)) {
		return 0;
	}

	if (!is_dir($destDir)) {
		if (!mkdir($destDir))
			return -2;
	}
	$dir = dir($sourceDir);
	$dirItems = array();
	while (false !== ($entry = $dir->read())) {
		array_push($dirItems, $entry);
	}
	$dir->close();

	foreach ($dirItems as $dirItem) {
		if (preg_match("/^\./", $dirItem))
			continue;
		if (is_dir($sourceDir.DIRECTORY_SEPARATOR.$dirItem)) {
			filterDirectory($sourceDir.DIRECTORY_SEPARATOR.$dirItem, $destDir.DIRECTORY_SEPARATOR.$dirItem);
		}
		else {
			if (preg_match("/\.php$/", $dirItem)) {
				filterFile($sourceDir, $destDir, $dirItem);
			}
			elseif (preg_match("/\.dox$/", $dirItem)) {
				@copy($sourceDir.DIRECTORY_SEPARATOR.$dirItem, $destDir.DIRECTORY_SEPARATOR.$dirItem);
			}
		}
	}
}

function filterFile($sourceDir, $destDir, $fileName) {
	$sourceFile = $sourceDir.DIRECTORY_SEPARATOR.$fileName;
	$destFile   = $destDir.DIRECTORY_SEPARATOR.$fileName;
	if (!file_exists($sourceFile) || !is_readable($sourceFile)) {
		return -1;
	}

	// vytvorenie adresára
	if (!is_dir($destDir)) {
		if (!mkdir($destDir))
			return -2;
	}
	$filePath = explode(DIRECTORY_SEPARATOR, $fileName);
	for ($i = 0; $i < count($filePath) - 1; ++$i) {
		$destDir .= DIRECTORY_SEPARATOR.$filePath[$i];
		if (!is_dir($destDir)) {
			if (!mkdir($destDir))
				return -2;
		}
	}

	$data = filter($sourceFile);
	if (file_put_contents($destFile, $data) === false)
		return -3;
	return 0;
}


?>