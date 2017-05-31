<?php

/**
 *
 * @copyright  2010-2017 (2016) izend.org
 * @version    7 (1)
 * @link       http://www.izend.org
 */

require_once 'userhasrole.php';
require_once 'models/node.inc';

function editnode($lang, $arglist=false) {
	global $supported_languages, $supported_contents;

	if (!user_has_role('writer')) {
		return run('error/unauthorized', $lang);
	}

	$node=false;

	if (is_array($arglist)) {
		if (isset($arglist[0])) {
			$node=$arglist[0];
		}
	}

	if (!$node) {
		return run('error/notfound', $lang);
	}

	$node_id = node_id($node);
	if (!$node_id) {
		return run('error/notfound', $lang);
	}

	$clang=isset($_POST['clang']) ? $_POST['clang'] : (isset($_GET['clang']) ? $_GET['clang'] : $lang);

	if (!in_array($clang, $supported_languages)) {
		return run('error/notfound', $lang);
	}

	$node_editor = build('nodeeditor', $lang, $clang, $node_id, $supported_contents);

	head('title', $node_id);
	head('description', false);
	head('keywords', false);
	head('robots', 'noindex, nofollow');

	$view=url('node', $lang) . '/'. $node_id . '?' . 'clang=' . $clang;

	$banner = build('banner', $lang, compact('view'));

	$content = view('editing/editnode', $lang, compact('node_editor'));

	$output = layout('editing', compact('lang', 'banner', 'content'));

	return $output;
}

