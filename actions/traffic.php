<?php

/**
 *
 * @copyright  2016 (2017) izend.org
 * @version    3 (1)
 * @link       http://www.izend.org
 */

require_once 'userhasrole.php';

function traffic($lang) {
	global $googleanalyticsaccount, $googleanalyticskeyfile;

	if (!user_has_role('administrator')) {
		return run('error/unauthorized', $lang);
	}

	if (! ($googleanalyticsaccount and $googleanalyticskeyfile)) {
		return run('error/internalerror', $lang);
	}

	head('title', translate('traffic:title', $lang));

	$admin=true;
	$banner = build('banner', $lang, compact('admin'));

	$analytics = build('analytics', $lang);

	$content=view('traffic', $lang, compact('analytics'));

	$output = layout('standard', compact('lang', 'banner', 'content'));

	return $output;
}

