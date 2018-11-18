<?php

/**
 *
 * @copyright  2010-2018 (2018) izend.org
 * @version    11 (3)
 * @link       http://www.izend.org
 */

require_once 'userhasrole.php';

function threadall($lang, $clang) {
	$site_title=translate('title', $clang);
	$site_abstract=translate('description', $clang);
	$site_cloud=translate('keywords', $clang);

	$inlanguages=view('inlanguages', false, compact('clang'));

	head('title', translate('threadall:title', $lang));
	head('description', false);
	head('keywords', false);
	head('robots', 'noindex');

	$edit=user_has_role('writer') ? url('threadedit', $_SESSION['user']['locale']) . '?' . 'clang=' . $clang : false;

	$banner = build('banner', $lang, compact('edit'));

	$threadlist = build('threadlist', $clang, false, false, $lang);

	$content = view('threadall', $lang, compact('site_title', 'site_abstract', 'site_cloud', 'threadlist', 'inlanguages'));

	$output = layout('viewing', compact('clang', 'banner', 'content'));

	return $output;
}

