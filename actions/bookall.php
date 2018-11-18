<?php

/**
 *
 * @copyright  2010-2018 (2016) izend.org
 * @version    3 (1)
 * @link       http://www.izend.org
 */

require_once 'userhasrole.php';

function bookall($lang) {
	head('title', translate('bookall:title', $lang));

	$edit=user_has_role('writer') ? url('bookedit', $_SESSION['user']['locale']) . '?' . 'clang=' . $lang : false;
	$validate=url('book', $lang);

	$banner = build('banner', $lang, compact('edit', 'validate'));

	$booklist = build('threadlist', $lang, 'book');

	$content = view('bookall', $lang, compact('booklist'));

	$output = layout('standard', compact('lang', 'banner', 'content'));

	return $output;
}

