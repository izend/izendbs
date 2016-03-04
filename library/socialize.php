<?php

/**
 *
 * @copyright  2010-2015 (2016) izend.org
 * @version    3 (1)
 * @link       http://www.izend.org
 */

function socialize($lang, $components=false) {
	global $socializing, $socializingmode;

	$besocial=false;

	if ($socializing) {
		$besocial=build('besocial', $lang, $components, $socializingmode);
	}

	return $besocial;
}

