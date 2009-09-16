<?php


function filter($fileName) {
	if (!file_exists($fileName) || !is_readable($fileName)) {
		echo "error: File doesn't exists or is not readable!\n";
		die(-255);
	}

	$fileContents = file_get_contents($fileName);
	$code = extractPHPCode($fileContents);
	$out  = processPHPCode($code);

	return '<?php' . $out . '?>';
}

function extractPHPCode($fileContents) {
	$phpPattern = "/<\?php(.*?)\?>/ism";
	$codeOffset = 0;
	$match  = array();
	$found  = true;
	$code   = '';
	while ($found) {
		preg_match($phpPattern, $fileContents, $match, PREG_OFFSET_CAPTURE, $codeOffset);
		$found = count($match) > 0;

		if ($found) {
			$codeOffset = $match[0][1] + strlen($match[0][0]);
			$code .= $match[1][0];
		}
	}
	return $code;
}

function processPHPCode($code) {
	$commentPattern = "/\/\*\*(.*?)\*\/\n/sm";
	$codeOffset     = 0;
	$outCode        = '';
	$match          = array();
	$found          = true;

	while ($found) {
	
		preg_match($commentPattern, $code, $commentMatch, PREG_OFFSET_CAPTURE, $codeOffset);
		$found = count($commentMatch) > 0;

		if ($found) {
			$comment = $commentMatch[1][0];
			$commentOffset = $commentMatch[1][1];
			$codeOffset = $commentMatch[0][1] + strlen($commentMatch[0][0]);

			$data = processVar($code, $comment, $codeOffset, $commentOffset);

			$code       = $data['code'];
			$comment    = $data['comment'];
			$codeOffset = $data['offset'];

			$data = processFunction($code, $comment, $codeOffset, $commentOffset);

			$code       = $data['code'];
			$comment    = $data['comment'];
			$codeOffset = $data['offset'];
		}
	}
	return $code;
}

function processVar($code, $comment, $codeOffset, $commentOffset) {
	$varPattern = "/^\s*(?:(?:(?:var|public|private|protected|static)\s+)+)\s*\\$([\w\d]*)/is";
	preg_match($varPattern, substr($code, $codeOffset), $varMatch, PREG_OFFSET_CAPTURE);
	// bola nájdená premenná
	if (count($varMatch) > 0) {
		preg_match("/(?:\\\|@)var\s+(\S*)/is", $comment, $varTypeMatch, PREG_OFFSET_CAPTURE);
		// nájdený typ premennej
		if (count($varTypeMatch) > 0) {
			// typ premennej
			$varType = $varTypeMatch[1][0];
			// oblasť, do ktorej sa vloží typ premennej
			$varOffset = $varMatch[1][1] - 1 + $codeOffset;

			// vloženie typu premennej
			$code = substr_replace($code, $varType . ' ', $varOffset, 0);

			// odstránenie typu premennej z komentára
			$newComment = substr_replace($comment, '', $varTypeMatch[0][1], strlen($varTypeMatch[0][0]));
		}
	}
	if (isset($newComment)) {
		$code = substr_replace($code, $newComment, $commentOffset, strlen($comment));
		$codeOffset += strlen($newComment) - strlen($comment);
		$comment = $newComment;
	}
	return array('code' => $code, 'comment' => $comment, 'offset' => $codeOffset);
}

function processFunction($code, $comment, $codeOffset, $commentOffset) {
	$funcPattern = "/^\s*((?:(?:public|private|protected|static|abstract|final)\s*)*)function\s*(?:&?)\s*([\w\d]*)\((.*?)\)/is";
	preg_match($funcPattern, substr($code, $codeOffset), $funcMatch, PREG_OFFSET_CAPTURE);
	if (count($funcMatch) > 0) {
		// vyhľadanie parametrov
		$paramTypes = array();
		preg_match_all("/(?:\\\|@)param\s+(\S*)\s+(\S*)/is", $comment, $paramsMatchAll, PREG_OFFSET_CAPTURE);
		for ($i = count($paramsMatchAll[0]) - 1; $i >= 0; --$i) {
			$type = $paramsMatchAll[1][$i][0];
			$name = $paramsMatchAll[2][$i][0];
			$paramTypes[$name] = $type;
			if (!isset($newComment))
				$newComment = $comment;
			if (preg_match("/^[\s\*]*(?(?=(\\\|@)).*)$/is", substr($comment, $paramsMatchAll[0][$i][1] + strlen($paramsMatchAll[0][$i][0]))))
				$newComment = substr_replace($newComment, '', $paramsMatchAll[0][$i][1], strlen($paramsMatchAll[0][$i][0]));
			else
				$newComment = substr_replace($newComment, '', $paramsMatchAll[1][$i][1], strlen($type));
		}
		if (isset($newComment)) {
			$code = substr_replace($code, $newComment, $commentOffset, strlen($comment));
			$codeOffset += strlen($newComment) - strlen($comment);
			$comment = $newComment;
			unset($newComment);
		}

		// nahradenie parametrov
		$funcParams =  $funcMatch[3][0];
		$funcParamsOffset = $funcMatch[3][1] + $codeOffset;
		$funcParamsArr = explode(',', $funcParams);
		$newFuncParams = array();
		foreach ($funcParamsArr as $funcParam) {
			if (preg_match("/(\S*)\s*\\\$([\w\d]*)(.*)/ism", $funcParam, $funcParamMatch)) {
				$name = $funcParamMatch[2];
				$type = $funcParamMatch[1];
				if (isset($paramTypes[$name])) {
					$type = $paramTypes[$name];
				}
				array_push($newFuncParams, $type . " \$" . $name . $funcParamMatch[3]);
			}
		}
		$newFuncParams = implode(', ', $newFuncParams);
		$code = substr_replace($code, $newFuncParams, $funcParamsOffset, strlen($funcParams));

		// nahradenie návratového typu
		preg_match("/((?:\\\|@)return\s+(\S+))/is", $comment, $retTypeMatch, PREG_OFFSET_CAPTURE);
		// nájdený návratový typ
		if (count($retTypeMatch) > 0) {
			// návratový typ funkcie
			$retType = $retTypeMatch[2][0];
			// oblasť pre vloženie návratového typu
			$retOffset = $funcMatch[1][1] + strlen($funcMatch[1][0]) + $codeOffset;

			// vloženie návratového typu
			$code = substr_replace($code, $retType . ' ', $retOffset, 0);

			// prázdne
			if (preg_match("/^[\s\*]*(?(?=(\\\|@)).*)$/is", substr($comment, $retTypeMatch[0][1] + strlen($retTypeMatch[0][0]))))
				$newComment = substr_replace($comment, '', $retTypeMatch[1][1], strlen($retTypeMatch[1][0]));
			else {
				$newComment = substr_replace($comment, '', $retTypeMatch[2][1], strlen($retTypeMatch[2][0]));
			}
		}
	}
	if (isset($newComment)) {
		$code = substr_replace($code, $newComment, $commentOffset, strlen($comment));
		$codeOffset += strlen($newComment) - strlen($comment);
		$comment = $newComment;
	}
	return array('code' => $code, 'comment' => $comment, 'offset' => $codeOffset);
}

?>
