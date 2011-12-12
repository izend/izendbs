<?php

/**
 *
 * @copyright  2010-2011 izend.org
 * @version    1
 * @link       http://www.izend.org
 */

require_once 'filemimetype.php';

function emailhtml($text, $html, $to, $subject, $sender=false) {
	global $mailer, $webmaster;

	if (!$sender) {
		$sender = $webmaster;
	}

	$textheader=$textbody=$htmlheader=$htmlbody=false;

	if ($text) {
		$textheader = 'Content-Type: text/plain; charset=utf-8';
		$textbody = <<<_SEP_
$text

_SEP_;
	}

	$related=false;

	if ($html) {
		$related=array();
		if (preg_match_all('#<img[^>]+src="([^"]*)"[^>]*>#is', $html, $matches)) {
			$pattern=array();
			$replacement=array();
			foreach ($matches[1] as $url) {
				if ($url[0] != '/')
					continue;
				$fname=ROOT_DIR . $url;
				$filetype=file_mime_type($fname, false);
				if (strpos($filetype, 'image') !== 0)
					continue;
				$base64=chunk_split(base64_encode(file_get_contents($fname)));
				if (!$base64)
					continue;
				$cid=md5(uniqid('cid'));
				$qfname=preg_quote($url);
				$pattern[]='#(<img[^>]+src=)"' . $qfname . '"([^>]*>)#is';
				$replacement[]='${1}"cid:' . $cid . '"${2}';
				$related[]=array(basename($fname), $filetype, $cid, $base64);
			}

			$html=preg_replace($pattern, $replacement, $html, 1);
		}

		$htmlheader = 'Content-Type: text/html; charset=utf-8';
		$htmlbody = <<<_SEP_
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8">
<title></title>
</head>
<body>
$html
</body>
</html>

_SEP_;
	}

	$headers = <<<_SEP_
From: $sender
Return-Path: $sender
X-Mailer: $mailer

_SEP_;

	$body='';

	if ($related) {
		if ($textbody) {
			$sep=md5(uniqid('sep'));

			$body .= <<<_SEP_
Content-Type: multipart/alternative; boundary="$sep"

--$sep
$textheader

$textbody
--$sep
$htmlheader

$htmlbody
--$sep--


_SEP_;
		}
		else {
			$body .= <<<_SEP_
$htmlheader

$htmlbody

_SEP_;
		}

		$sep=md5(uniqid('sep'));

		$headers .= <<<_SEP_
Content-Type: multipart/related; boundary="$sep"
_SEP_;

		foreach ($related as $r) {
			list($filename, $filetype, $cid, $base64)=$r;
			$body .= <<<_SEP_
--$sep
Content-Type: $filetype
Content-Transfer-Encoding: base64
Content-Disposition: inline; filename="$filename"
Content-ID: <$cid>

$base64

_SEP_;
		}

		$body = <<<_SEP_
--$sep
$body
--$sep--
_SEP_;
	}
	else if ($textbody and $htmlbody) {
		$sep=md5(uniqid('sep'));

		$headers .= <<<_SEP_
Content-Type: multipart/alternative; boundary="$sep"
_SEP_;
		$body .= <<<_SEP_
--$sep
$textheader

$textbody
--$sep
$htmlheader

$htmlbody
--$sep--
_SEP_;
	}
	else if ($textbody) {
		$headers .= $textheader;
		$body=$textbody;
	}
	else if ($htmlbody) {
		$headers .= $htmlheader;
		$body=$htmlbody;
	}

	return @mail($to, $subject, $body, $headers);
}
