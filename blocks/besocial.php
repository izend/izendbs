<?php

/**
 *
 * @copyright  2010-2015 (2016) izend.org
 * @version    7 (1)
 * @link       http://www.izend.org
 */

function besocial($lang, $components, $sharemode) {
	$ilike=$tweetit=$plusone=$linkedin=$pinit=false;

	extract($components);	/* ilike, tweetit, plusone, linkedin, pinit */

	$mode=$sharemode == 'standard' ? 'inline' : 'lite';

	if ($ilike) {
		$ilike=view('ilike', $lang, compact('mode'));
	}
	if ($tweetit) {
		$tweet_text=false;
		if (is_array($tweetit)) {
			extract($tweetit);	/* tweet_text */
		}
		$tweet_text=preg_replace('/\s+/', ' ', trim($tweet_text));
		$tweetit=view('tweetit', $lang, compact('mode', 'tweet_text'));
	}
	if ($plusone) {
		$plusone=view('plusone', $lang, compact('mode'));
	}
	if ($linkedin) {
		$linkedin=view('linkedin', $lang, compact('mode'));
	}
	if ($pinit) {
		$pinit_text=$pinit_image=false;
		if (is_array($pinit)) {
			extract($pinit);	/* pinit_text pinit_image */
		}
		$pinit=view('pinit', $lang, compact('mode', 'pinit_text', 'pinit_image'));
	}

	$output = view('besocial', false, compact('shareline', 'sharemode', 'ilike', 'tweetit', 'plusone', 'linkedin', 'pinit'));

	return $output;
}

