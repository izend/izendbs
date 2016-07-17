<?php

/**
 *
 * @copyright  2010-2012 (2016) izend.org
 * @version    2 (1)
 * @link       http://www.izend.org
 */

function unauthorized($lang) {
	head('title', translate('http_unauthorized:title', $lang));
	head('robots', 'noindex, nofollow');

	$contact=$account=true;
	$banner = build('banner', $lang, compact('contact', 'account'));

	$contact_page=url('contact', $lang);
	$content = view('error/unauthorized', $lang, compact('contact_page'));

	$output = layout('standard', compact('lang', 'banner', 'content'));

	header('HTTP/1.1 401 Unauthorized');

	return $output;
}

