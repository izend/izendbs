<?php

/**
 *
 * @copyright  2010-2018 (2016) izend.org
 * @version    3 (1)
 * @link       http://www.izend.org
 */

function badrequest($lang) {
	head('title', translate('http_bad_request:title', $lang));
	head('robots', 'noindex');

	$contact=true;
	$banner = build('banner', $lang, compact('contact'));

	$contact_page=url('contact', $lang);
	$content = view('error/badrequest', $lang, compact('contact_page'));

	$output = layout('standard', compact('lang', 'banner', 'content'));

	header('HTTP/1.1 400 Bad Request');

	return $output;
}

