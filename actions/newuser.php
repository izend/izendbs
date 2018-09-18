<?php

/**
 *
 * @copyright  2010-2018 (2016-2018) izend.org
 * @version    3 (2)
 * @link       http://www.izend.org
 */

function newuser($lang) {
	head('title', translate('newuser:title', $lang));
	head('description', false);
	head('keywords', false);
	head('robots', 'noindex');

	$admin=true;
	$banner = build('banner', $lang, compact('admin'));

	$register = build('register', $lang);

	$content = view('newuser', $lang, compact('register'));

	$output = layout('standard', compact('lang', 'banner', 'content'));

	return $output;
}

