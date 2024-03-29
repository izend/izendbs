<?php

/**
 *
 * @copyright  2022 (2018) izend.org
 * @version    4 (1)
 * @link       http://www.izend.org
 */

require_once 'models/newsletter.inc';

function confirmnewslettersubscribe($lang, $arglist) {
	head('title', translate('newsletter:title', $lang));
	head('description', false);
	head('keywords', false);
	head('robots', 'noindex');

	$banner = build('banner', $lang);

	list($timestamp, $mail)=$arglist;

	$bad_mail=false;
	$bad_time=false;

	if (newsletter_get_user($mail)) {
		$bad_mail=true;
	}

	if (time() - $timestamp > 3600) {
		$bad_time=true;
	}

	$subscribe_page=$unsubscribe_page=false;

	$internal_error=false;
	$contact_page=false;

	if ($bad_mail) {
		$unsubscribe_page=url('newsletterunsubscribe', $lang);
	}
	else if ($bad_time) {
		$subscribe_page=url('newslettersubscribe', $lang);
	}
	else {
		$r = newsletter_create_user($mail, $lang);

		if (!$r) {
			$internal_error=true;
		}
		else {
			require_once 'serveripaddress.php';
			require_once 'emailme.php';

			global $sitename;

			$ip=server_ip_address();
			$timestamp=date('Y-m-d H:i:s');
			$subject = 'subscribe' . '@' . $sitename;
			$msg = $ip . ' ' . $timestamp . ' ' . $lang . ' ' . $mail;
			@emailme($subject, $msg);

			$unsubscribe_page=url('newsletterunsubscribe', $lang);
		}
	}

	if ($internal_error) {
		$contact_page=url('contact', $lang);
	}

	$errors = compact('bad_mail', 'bad_time', 'internal_error', 'contact_page');

	$content = view('confirmnewslettersubscribe', $lang, compact('mail', 'subscribe_page', 'unsubscribe_page', 'errors'));

	$output = layout('standard', compact('lang', 'banner', 'content'));

	return $output;
}

