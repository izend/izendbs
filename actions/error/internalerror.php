<?php

/**
 *
 * @copyright  2010-2018 (2016) izend.org
 * @version    3 (1)
 * @link       http://www.izend.org
 */

function internalerror($lang) {
	head('title', translate('http_internal_error:title', $lang));
	head('robots', 'noindex');

	$contact=true;
	$banner = build('banner', $lang, compact('contact'));

	$contact_page=url('contact', $lang);
	$content = view('error/internalerror', $lang, compact('contact_page'));

	$output = layout('standard', compact('lang', 'banner', 'content'));

	header('HTTP/1.1 500 Internal Error');

	return $output;
}

