<?php

/**
 *
 * @copyright  2016-2023 (2017) izend.org
 * @version    6 (1)
 * @link       http://www.izend.org
 */

require_once 'userhasrole.php';

function traffic($lang) {
	global $googlecredentials;

	if (!user_has_role('administrator')) {
		return run('error/unauthorized', $lang);
	}

	if (! ($googlecredentials)) {
		return run('error/internalerror', $lang);
	}

	$analytics = build('analytics', $lang);

	$content=view('traffic', $lang, compact('analytics'));

	head('title', translate('traffic:title', $lang));
	head('description', false);
	head('keywords', false);
	head('robots', 'noindex');

	$admin=true;
	$banner = build('banner', $lang, compact('admin'));

	$output = layout('standard', compact('lang', 'banner', 'content'));

	return $output;
}

