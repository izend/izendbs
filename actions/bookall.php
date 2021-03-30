<?php

/**
 *
 * @copyright  2010-2021 (2016) izend.org
 * @version    4 (1)
 * @link       http://www.izend.org
 */

require_once 'userhasrole.php';
require_once 'userhasaccess.php';

function bookall($lang) {
	head('title', translate('bookall:title', $lang));

	$edit=user_has_role('writer') ? url('bookedit', $_SESSION['user']['locale']) . '?' . 'clang=' . $lang : false;
	$validate=url('book', $lang);

	$banner = build('banner', $lang, compact('edit', 'validate'));

	$not_in=user_noread_list();

	$booklist = build('threadlist', $lang, 'book', $not_in);

	$content = view('bookall', $lang, compact('booklist'));

	$output = layout('standard', compact('lang', 'banner', 'content'));

	return $output;
}

