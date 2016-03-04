<?php

/**
 *
 * @copyright  2010-2012 (2016) izend.org
 * @version    1 (1)
 * @link       http://www.izend.org
 */

function notfound($lang) {
	head('title', translate('http_not_found:title', $lang));
	head('robots', 'noindex, nofollow');

	$contact=true;
	$banner = build('banner', $lang, compact('contact'));

	$contact_page=url('contact', $lang);
	$content = view('error/notfound', $lang, compact('contact_page'));

	$output = layout('standard', compact('lang', 'banner', 'content'));

	header('HTTP/1.1 404 Not Found');

	return $output;
}

