<?php

/**
 *
 * @copyright  2010-2012 (2016) izend.org
 * @version    3 (1)
 * @link       http://www.izend.org
 */

function paymentrejected($lang, $amount, $currency, $context) {
	head('title', translate('payment_rejected:title', $lang));
	head('robots', 'noindex, nofollow');

	$contact=true;
	$banner = build('banner', $lang, compact('contact'));

	$contact_page=url('contact', $lang);
	$content = view('paymentrejected', $lang, compact('amount', 'currency', 'contact_page'));

	$output = layout('standard', compact('lang', 'banner', 'content'));

	return $output;
}

