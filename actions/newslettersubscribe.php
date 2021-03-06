<?php

/**
 *
 * @copyright  2012-2018 (2016) izend.org
 * @version    4 (1)
 * @link       http://www.izend.org
 */

function newslettersubscribe($lang) {
	head('title', translate('newsletter:title', $lang));
	head('description', false);
	head('keywords', false);
	head('robots', 'noindex');

	$banner = build('banner', $lang);

	$subscribe = build('subscribe', $lang);

	$content = view('newslettersubscribe', $lang, compact('subscribe'));

	$output = layout('standard', compact('lang', 'banner', 'content'));

	return $output;
}

