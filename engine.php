<?php

/*
 * PHBeam - Simple and fast PHP micro-framework
 * Version 0.1.0
 * Created by ParadigmCode: https://paradigmcode.net
 * GitHub repo: https://github.com/ParadigmCode/PHBeam
 */

$base_dir = __DIR__;

$config = resource_json('config');

$default_layout = $config['default_layout'];
$error_page_layout = $config['error_page_layout'];

$menu = [];

foreach ($config['menus'] as $menu_name) {
	${"menu_$menu_name"} = resource_json('content/_menu_' . $menu_name);
	$menu += ${"menu_$menu_name"};
}

$path = strval(substr(parse_url(filter_input(INPUT_SERVER, 'REQUEST_URI', FILTER_SANITIZE_URL), PHP_URL_PATH), 1));

parse_str(filter_input(INPUT_SERVER, 'QUERY_STRING', FILTER_SANITIZE_URL), $url_params);

$allowed_url_params = $config['allowed_url_params'];
$blocked_params = [];

foreach ($url_params as $param => $value) {
	if (!in_array($param, $allowed_url_params)) {
		$blocked_params[] = $param;
	}
}

if (array_key_exists($path, $menu) && file_exists("$base_dir/content/{$menu[$path]['file']}.php") && empty($blocked_params)) {
	$page = $menu[$path]['file'];
	$body_class = $menu[$path]['body_class'];
	$layout = $menu[$path]['layout'] ?: $default_layout;
} else {
	header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
	$page = '404';
	$body_class = 'error';
	$layout = $error_page_layout;
}

$meta = resource_json("content/{$page}_meta");

ob_start();
require "$base_dir/content/$page.php";
$article = ob_get_clean();

require $base_dir . '/layouts/main.php';

function resource_json($filename)
{
	$path = "{$GLOBALS['base_dir']}/$filename.json";

	if (!file_exists($path)) return false;

	return json_decode(file_get_contents($path), true);
}

function layout($article, $layout = null)
{
	if ($layout) {
		$path = "{$GLOBALS['base_dir']}/layouts/$layout.php";

		if (!file_exists($path)) return false;

		include $path;
	} else {
		echo $article;
	}
}

function module($module, $params = null)
{
	$path = "{$GLOBALS['base_dir']}/modules/$module.php";

	if (!file_exists($path)) return false;

	include $path;
}

function position_modules($position)
{
	$file_prefix = $GLOBALS['menu'][$GLOBALS['path']]['file'] ? $GLOBALS['menu'][$GLOBALS['path']]['file'] : '404';

	$modules = resource_json("content/{$file_prefix}_modules");

	if ($modules && array_key_exists($position, $modules) && !empty($modules[$position])) {
		return $modules;
	} else {
		return false;
	}
}

function position($position)
{
	$modules = position_modules($position);

	if (!$modules) return false;

	foreach ($modules[$position] as $module_name => $module) {
		echo "<div class=\"module module-$module_name\">";
		module($module_name, $module);
		echo '</div>';
	}
}

function resource_static($filename)
{
	$file = "{$GLOBALS['base_dir']}/public_html/$filename";

	if (!file_exists($file)) return false;

	$modified_at = filemtime($file);

	return "$filename?v=$modified_at";
}

function css($filename)
{
	$filename = resource_static("css/$filename.css");
	echo "<link rel=\"stylesheet\" href=\"/$filename\">";
}

function js($filename)
{
	$filename = resource_static("js/$filename.js");
	echo "<script src=\"/$filename\"></script>";
}

function image_size($filename)
{
	$size = getimagesize($filename);
	$result_size = $size[0] . 'x' . $size[1];

	return $result_size;
}
