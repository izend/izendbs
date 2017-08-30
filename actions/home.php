<?php

/**
 *
 * @copyright  2010-2017 (2016) izend.org
 * @version    16 (1)
 * @link       http://www.izend.org
 */

require_once 'socialize.php';
require_once 'userhasrole.php';
require_once 'models/node.inc';

function home($lang) {
	global $root_node, $request_path, $sitename, $siteshot;

	if (!$root_node) {
		return run('error/internalerror', $lang);
	}

	$r = node_get($lang, $root_node);
	if (!$r) {
		return run('error/notfound', $lang);
	}
	extract($r); /* node_name node_title node_abstract node_cloud node_image node_created node_modified node_nocomment node_nomorecomment node_ilike node_tweet node_plusone node_linkedin node_pinit */

	head('title', translate('home:title', $lang));
	if ($node_abstract) {
		head('description', $node_abstract);
	}
	else {
		head('description', translate('description', $lang));
	}
	if ($node_cloud) {
		head('keywords', $node_cloud);
	}
	else {
		head('keywords', translate('keywords', $lang));
	}
	head('date', $node_modified);

	$request_path=$lang;

	$page_contents = build('nodecontent', $lang, $root_node);

	$besocial=false;
	if ($page_contents) {
		$ilike=$node_ilike;
		$tweetit=$node_tweet;
		$plusone=$node_plusone;
		$linkedin=$node_linkedin;
		$pinit=$node_pinit;
		if ($tweetit or $pinit) {
			$description=$node_abstract ? $node_abstract : translate('description', $lang);
			if ($tweetit) {
				$tweet_text=$description ? $description : $sitename;
				$tweetit=$tweet_text ? compact('tweet_text') : true;
			}
			if ($pinit) {
				$pinit_text=$description ? $description : $sitename;
				$pinit_image=$node_image ? $node_image : $siteshot;
				$pinit=$pinit_text && $pinit_image ? compact('pinit_text', 'pinit_image') : true;
			}
		}
		$besocial = socialize($lang, compact('ilike', 'tweetit', 'plusone', 'linkedin', 'pinit'));
	}

	$content = view('home', false, compact('page_contents', 'besocial'));

	$languages='home';
	$contact=$account=$admin=$donate=true;
	$edit=user_has_role('writer') ? url('editpage', $_SESSION['user']['locale']) . '/'. $root_node . '?' . 'clang=' . $lang : false;
	$validate=url('home', $lang);
	$search_text='';
	$search_url=url('search', $lang);
	$suggest_url=url('suggest', $lang);
	$search=compact('search_url', 'search_text', 'suggest_url');

	$banner = build('banner', $lang, compact('languages', 'contact', 'account', 'admin', 'search', 'donate', 'edit', 'validate'));

	$search_text='';
	$search_url=url('search', $lang);
	$suggest_url=url('suggest', $lang);
	$search=view('searchinput', $lang, compact('search_url', 'search_text', 'suggest_url'));
	$sidebar = view('sidebar', false, compact('search'));

	$languages=true;
	$contact=true;
	$newsletter=false;
	$account=$admin=true;
	$footer = build('footer', $lang, compact('languages', 'contact', 'newsletter', 'account', 'admin'));

	$output = layout('standard', compact('lang', 'banner', 'content', 'footer', 'sidebar'));

	return $output;
}

