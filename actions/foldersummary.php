<?php

/**
 *
 * @copyright  2010-2021 (2016) izend.org
 * @version    7 (1)
 * @link       http://www.izend.org
 */

require_once 'userhasrole.php';
require_once 'userhasaccess.php';
require_once 'models/thread.inc';

function foldersummary($lang, $folder) {
	$folder_id = thread_id($folder);
	if (!$folder_id) {
		return run('error/notfound', $lang);
	}

	if (!user_can_read($folder_id)) {
		return run('error/unauthorized', $lang);
	}

	$r = thread_get($lang, $folder_id);
	if (!$r) {
		return run('error/notfound', $lang);
	}
	extract($r); /* thread_type thread_name thread_title thread_abstract thread_cloud thread_image */

	if ($thread_type != 'folder') {
		return run('error/notfound', $lang);
	}

	$folder_name = $thread_name;
	$folder_title = $thread_title;
	$folder_abstract = $thread_abstract;
	$folder_cloud = $thread_cloud;
	$folder_image = $thread_image;

	if ($folder_title) {
		head('title', $folder_title);
	}
	if ($folder_abstract) {
		head('description', $folder_abstract);
	}
	if ($folder_cloud) {
		head('keywords', $folder_cloud);
	}
	if ($folder_image) {
		head('image', $folder_image);
	}

	$folder_contents = array();
	$r = thread_get_contents($lang, $folder_id);
	if ($r) {
		$folder_url = url('folder', $lang) . '/'. $folder_name;
		foreach ($r as $c) {
			extract($c);	/* node_name node_title */
			if (!$node_title) {
				continue;
			}
			$page_title = $node_title;
			$page_url = $folder_url  . '/' . $node_name;
			$folder_contents[] = compact('page_title' , 'page_url');
		}
	}

	$content = view('foldersummary', false, compact('folder_id', 'folder_title', 'folder_contents'));

	$edit=user_has_role('writer') ? url('folderedit', $_SESSION['user']['locale']) . '/'. $folder_id . '?' . 'clang=' . $lang : false;
	$validate=url('folder', $lang) . '/'. $folder_name;

	$banner = build('banner', $lang, compact('edit', 'validate'));

	$output = layout('standard', compact('lang', 'banner', 'content'));

	return $output;
}

