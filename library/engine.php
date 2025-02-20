<?php

/**
 *
 * @copyright   2010-2024 izend.org
 * @version     19
 * @link        http://www.izend.org
 */

global $aliases;

$aliases = array();

@include 'aliases.inc';

require_once 'requesturi.php';
require_once 'translate.php';
require_once 'head.php';
require_once 'trace.php';
require_once 'track.php';

define('ACTIONS_DIR', ROOT_DIR . DIRECTORY_SEPARATOR . 'actions');
define('BLOCKS_DIR', ROOT_DIR . DIRECTORY_SEPARATOR . 'blocks');
define('VIEWS_DIR', ROOT_DIR . DIRECTORY_SEPARATOR . 'views');
define('LAYOUTS_DIR', ROOT_DIR . DIRECTORY_SEPARATOR . 'layouts');

function url($action, $lang=false, $arg=false, $param=false) {
	global $base_path;

	$path = alias($action, $lang, $arg);

	if ($path === false) {
		return false;
	}

	$url = $base_path . '/' . $path;

	if ($param) {
		if (is_array($param) and $lang and array_key_exists($lang, $param)) {
			$param=$param[$lang];
		}
		if (is_array($param)) {
			$p=array();
			foreach ($param as $name => $value) {
				$p[]=urlencode($name) . '=' . urlencode($value);
			}
			$param=implode('&', $p);
		}
		$url .= '?' . $param;
	}

	return $url;
}

function alias($action, $lang=false, $arg=false) {
	$path = $action ? detour($action, $lang) : '';

	if ($path === false) {
		return false;
	}

	if ($arg) {
		if ($action) {
			$path .= '/';
		}
		if (is_array($arg) and $lang and array_key_exists($lang, $arg)) {
			$arg=$arg[$lang];
		}
		$path .= is_array($arg) ? implode('/', $arg) : $arg;
	}

	return $lang ? $lang . '/' . $path : $path;
}

function detour($action, $lang=false) {
	global $aliases;

	if ($lang && array_key_exists($lang, $aliases)) {
		if (($path = array_search($action, $aliases[$lang]))) {
			return $path;
		}
	}
	if (array_key_exists(0, $aliases)) {
		if (($path = array_search($action, $aliases[0]))) {
			return $path;
		}
	}

	return false;
}

function route($query, $lang=false) {
	global $aliases, $default_action, $home_action;

	$args = array();

	if (empty($query)) {
		return array($home_action, false);
	}

	$s = explode('/', $query);

	while (count($s) > 0) {
		$p = implode('/', $s);
		if ($lang && array_key_exists($lang, $aliases) && array_key_exists($p, $aliases[$lang])) {
			return array($aliases[$lang][$p], $args);
		}
		if (array_key_exists(0, $aliases) && array_key_exists($p, $aliases[0])) {
			return array($aliases[0][$p], $args);
		}
		array_unshift($args, array_pop($s));
	}

	return $default_action ? array($default_action, $args) : false;
}

function dispatch($languages) {
	global $base_path;
	global $request_path, $request_query;
	global $closing_time, $opening_time;
	global $track_visitor, $track_visitor_agent;

	$req = $base_path ? substr(request_uri(), strlen($base_path)) : request_uri();

	if ($track_visitor) {
		track($req, $track_visitor_agent);
	}

	$url = @parse_url($req);
	$path = isset($url['path']) ? trim(urldecode($url['path']), '/') : false;
	$query = isset($url['query']) ? $url['query'] : false;

	$request_query=$query;

	if (empty($path)) {
		$path = false;
	}

	/* site language */
	$p = $path ? explode('/', $path) : false;
	$lang = $p ? $p[0] : false;

	if ($lang && in_array($lang, $languages, true)) {
		array_shift($p);
		$path = implode('/', $p);
	}
	else {
		require_once 'locale.php';

		$lang=locale();

		if (!$lang or !in_array($lang, $languages, true)) {
			$lang = $languages[0];
		}
	}

	$request_path=$path ? $lang . '/' . $path : $lang;

	$action=$args=$params=false;
	if ($closing_time) {
		$action='error/serviceunavailable';
		$args=array($closing_time, $opening_time);
	}
	else {
		$r = route($path, $lang);
		if (!$r) {
			$action='error/notfound';
		}
		else {
			list($action, $args) = $r;

			if ($query) {
				parse_str($query, $params);
			}
		}
	}

	$arglist = $args ? $params ? array_merge($args, $params) : $args : $params;

	run($action, $lang, $arglist);
}

function run($action, $lang=false, $arglist=false) {
	global $theme, $author;

	head('lang', $lang);
	head('title', translate('title', $lang));
	head('description', translate('description', $lang));
	head('keywords', translate('keywords', $lang));
	head('author', $author);
	head('favicon', 'favicon');
	head('theme', $theme);

	$file = ACTIONS_DIR . DIRECTORY_SEPARATOR . $action . '.php';
	if (!is_file($file)) {
		$action = 'error/internalerror';
		$file = ACTIONS_DIR . DIRECTORY_SEPARATOR . $action . '.php';
		$arglist = false;
	}
	require_once $file;

	$func = basename($action);

	$farg = array();
	if ($lang) {
		$farg[] = $lang;
	}
	if ($arglist) {
		$farg[] = $arglist;
	}

	$output = call_user_func_array($func, $farg);

	if ($output) {
		echo $output;
	}

	exit;
}

function build($block) {
	$file = BLOCKS_DIR . DIRECTORY_SEPARATOR . $block . '.php';
	require_once $file;
	$func = basename($block);
	$args=func_get_args();
	array_shift($args);
	return call_user_func_array($func, $args);
}

function view($view, $lang=false, $vars=false) {
	$file = $lang ? VIEWS_DIR . DIRECTORY_SEPARATOR . $lang . DIRECTORY_SEPARATOR . $view . '.phtml' : VIEWS_DIR . DIRECTORY_SEPARATOR . $view . '.phtml';
	return render($file, $vars);
}

function layout($layout, $vars=false) {
	$head=view('head', false, head());
	if ($vars) {
		$vars['head'] = $head;
	}
	else {
		$vars = array('head' => $head);
	}
	$trace=trace();
	if ($trace) {
		$vars['trace'] = $trace;
	}
	$file = LAYOUTS_DIR . DIRECTORY_SEPARATOR . $layout . '.phtml';
	return render($file, $vars);
}

function render($file, $vars=false) {
	global $base_path, $base_url, $base_root;
	global $request_path, $request_query;
	global $sitename, $webmaster;
	global $supported_languages, $system_languages;

	if ($vars) {
		extract($vars);
	}
	ob_start();
	require $file;
	return ob_get_clean();
}

function redirect($action, $lang=false, $arg=false, $params=false, $code=false) {
	global $base_url;

	$url=$base_url . url($action, $lang, $arg, $params);

	reload($url, $code);
}

function reload($url, $code=false) {
	if (ob_get_level()) {
		ob_clean();
	}

	switch ($code) {
		default:
		case 303:
			header('HTTP/1.1 303 See Other');
			break;
		case 301:
			header('HTTP/1.1 301 Moved Permanently');
			break;
		case 302:
			header('HTTP/1.1 302 Found');
			break;
		case 307:
			header('HTTP/1.1 307 Temporary Redirect');
			break;
	}

	header("Location: $url");

	exit;
}
