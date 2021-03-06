<?php

/**
 *
 * @copyright  2012-2021 (2016) izend.org
 * @version    7 (1)
 * @link       http://www.izend.org
 */

require_once 'userhasrole.php';
require_once 'models/thread.inc';

function newslettersummary($lang, $newsletter) {
	global $search_cloud;

	$newsletter_id = thread_id($newsletter);
	if (!$newsletter_id) {
		return run('error/notfound', $lang);
	}

	$r = thread_get($lang, $newsletter_id);
	if (!$r) {
		return run('error/notfound', $lang);
	}
	extract($r); /* thread_name thread_title thread_abstract thread_cloud thread_nocloud thread_nosearch */

	$newsletter_name = $thread_name;
	$newsletter_title = $thread_title;
	$newsletter_abstract = $thread_abstract;
	$newsletter_cloud = $thread_cloud;
	$newsletter_modified= $thread_modified;
	$newsletter_nocloud = $thread_nocloud;
	$newsletter_nosearch = $thread_nosearch;

	if ($newsletter_title) {
		head('title', $newsletter_title);
	}
	head('description', false);
	head('keywords', false);
	head('robots', 'noindex');

	$newsletter_contents = array();
	$r = thread_get_contents($lang, $newsletter_id);
	if ($r) {
		$newsletter_url = url('newsletter', $lang);
		foreach ($r as $c) {
			extract($c);	/* node_id node_name node_title node_number */
			$page_id = $node_id;
			$page_title = $node_title;
			$page_url = $newsletter_url  . '/' . $node_name;
			$newsletter_contents[] = compact('page_id', 'page_title', 'page_url');
		}
	}

	$content = view('newslettersummary', false, compact('newsletter_id', 'newsletter_title', 'newsletter_abstract', 'newsletter_contents'));

	$search=false;
	if (!$newsletter_nosearch) {
		$search_text='';
		$search_url= url('search', $lang, $newsletter_name);
		$suggest_url= url('suggest', $lang, $newsletter_name);
		$search=view('searchinput', $lang, compact('search_url', 'search_text', 'suggest_url'));
	}

	$cloud=false;
	if (!$newsletter_nocloud) {
		$cloud_url= url('search', $lang, $newsletter_name);
		$byname=$bycount=$index=true;
		$cloud = build('cloud', $lang, $cloud_url, $newsletter_id, false, false, $search_cloud, compact('byname', 'bycount', 'index'));
	}

	$headline_text=	translate('newsletter:title', $lang);
	$headline_url=false;
	$headline = compact('headline_text', 'headline_url');
	$title = view('headline', false, $headline);

	$sidebar = view('sidebar', false, compact('search', 'cloud', 'title'));

	$search=!$newsletter_nosearch ? compact('search_url', 'search_text', 'suggest_url') : false;
	$edit=user_has_role('writer') ? url('newsletteredit', $_SESSION['user']['locale']) . '/'. $newsletter_id : false;
	$validate=url('newsletter', $lang) . '/'. $newsletter_name;

	$banner = build('banner', $lang, compact('headline', 'edit', 'validate', 'search'));

	$output = layout('standard', compact('lang', 'banner', 'content', 'sidebar'));

	return $output;
}

