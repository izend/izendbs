<?php

/**
 *
 * @copyright  2010-2021 izend.org
 * @version    4
 * @link       http://www.izend.org
 */

require_once 'sendhttp.php';
require_once 'filemimetype.php';

function qrdecode($file) {
	$url = 'https://zxing.org/w/decode';
	$files=array('f' => array('name' => basename($file), 'tmp_name' => $file, 'type' => file_mime_type($file)));

	$response=sendpost($url, false, $files, false);	// DON'T encode data in base64

	if (!$response or $response[0] != 200) {
		return false;
	}

	if (!preg_match('#<tr><td>Parsed Result</td><td><pre.*>(.*)</pre></td></tr>#', $response[2], $r)) {	// extract data - adapt when response format changes
		return false;
	}

	return strip_tags($r[1]);
}

