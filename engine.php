<?php

/**
 * PHBeam - Simple and fast PHP micro-framework
 * Version 0.2.0
 * Created by Nikita Privalov
 * GitHub repo: https://github.com/nikomtis/phbeam
 */

$base_dir = __DIR__;

$config = phb_get_data_from_php('config');

$menu = [];

foreach ($config['menus'] as $menu_name) {
    ${"menu_$menu_name"} = phb_get_data_from_php('menus/_menu_' . $menu_name);
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

$default_layout = $config['default_layout'];
$error_page_layout = $config['error_page_layout'];

if (array_key_exists($path, $menu) && file_exists("{$base_dir}/content/{$menu[$path]['file']}.php") && empty($blocked_params)) {
    $page = $menu[$path]['file'];
    $body_class = $menu[$path]['body_class'];
    $layout = $menu[$path]['layout'] ?: $default_layout;
} else {
    header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
    $page = '404';
    $body_class = 'error';
    $layout = $error_page_layout;
}

ob_start();
require "{$base_dir}/content/$page.php";
$article = ob_get_clean();

require "{$base_dir}/layouts/layout.php";

/**
 * Get data from PHP file
 *
 * @param string $filename Name of the PHP file without extension.
 *
 * @return mixed|false Data from PHP file or false if file not found.
 */
function phb_get_data_from_php($filename)
{
    $path = "{$GLOBALS['base_dir']}/$filename.php";

    if (!file_exists($path)) {
        return false;
    }

    return require $path;
}

/**
 * Get data from JSON file
 *
 * @param string $filename Name of the JSON file without extension.
 *
 * @return mixed|false Data from JSON file or false if file not found.
 */
function phb_get_data_from_json($filename)
{
    $path = "{$GLOBALS['base_dir']}/$filename.json";

    if (!file_exists($path)) {
        return false;
    }

    return json_decode(file_get_contents($path), true);
}

/**
 * Add title, description and keywords to the page
 *
 * @return void
 */
function phb_insert_meta_tags()
{
    $meta = phb_get_data_from_php("content/{$GLOBALS['page']}_meta");

    if ($meta['title']) {
        echo "<title>{$meta['title']}</title>";
    }

    if ($meta['description']) {
        echo "<meta name=\"description\" content=\"{$meta['description']}\">";
    }

    if ($meta['keywords']) {
        echo "<meta name=\"keywords\" content=\"{$meta['keywords']}\">";
    }
}

/**
 * Add CSS class to body tag if it has been specified for current page
 *
 * @return void
 */
function phb_add_body_class()
{
    if ($GLOBALS['body_class']) {
        echo " class=\"{$GLOBALS['body_class']}\"";
    }
}

/**
 * Insert page content to layout
 *
 * @return void
 */
function phb_insert_page_content()
{
    $layout = "{$GLOBALS['base_dir']}/layouts/{$GLOBALS['layout']}.php";

    if (file_exists($layout)) {
        include $layout;
    }
}

/**
 * Insert module
 *
 * @param string $module Name of the file from "modules" directory without extension.
 * @param mixed $params Data that can be used by module.
 *
 * @return void|false FALSE if file not found.
 */
function phb_insert_module($module, $params = null)
{
    $path = "{$GLOBALS['base_dir']}/modules/$module.php";

    if (!file_exists($path)) {
        return false;
    }

    include $path;
}

/**
 * Get modules in specified position for current page
 *
 * @param string $position Name of the position.
 *
 * @return array|false Array containing modules or FALSE if there is no modules in this position for current page.
 */
function phb_get_position_modules($position)
{
    $file_prefix = $GLOBALS['menu'][$GLOBALS['path']]['file'] ? $GLOBALS['menu'][$GLOBALS['path']]['file'] : '404';

    $modules = phb_get_data_from_php("content/{$file_prefix}_modules");

    if ($modules && array_key_exists($position, $modules) && !empty($modules[$position])) {
        return $modules;
    } else {
        return false;
    }
}

/**
 * Insert position for modules
 *
 * @param string $position Name of the position.
 *
 * @return void
 */
function phb_insert_position($position)
{
    $modules = phb_get_position_modules($position);

    phb_preview_array($modules);

    if ($modules) {
        foreach ($modules[$position] as $module_name => $module_params) {
            if (is_int($module_name)) {
                $module_name = $module_params;
                $module_params = null;
            }
    
            echo "<div class=\"{$GLOBALS['config']['modules_class_prefix']}_$module_name\">";
            phb_insert_module($module_name, $module_params);
            echo '</div>';
        }
    }
}

/**
 * Add timestamp to the file path
 *
 * @param string $filename Path to file in "public_html/" directory in format "foo/bar.txt".
 *
 * @return string|false Path with added timestamp in format "foo/bar.txt?v=1531411841" or FALSE if file not found.
 */
function phb_add_timestamp($filename)
{
    $file = "{$GLOBALS['base_dir']}/public_html/$filename";

    if (!file_exists($file)) {
        return false;
    }

    $modified_at = filemtime($file);

    return "$filename?v=$modified_at";
}

/**
 * Insert CSS file link with it's version
 *
 * @param string $filename Name of the CSS file in "public_html/css/" directory without extension.
 *
 * @return void
 */
function phb_insert_css($filename)
{
    $filename = phb_add_timestamp("css/$filename.css");

    if ($filename) {
        echo "<link rel=\"stylesheet\" href=\"/$filename\">";
    }
}

/**
 * Insert JS file link with it's version
 *
 * @param string $filename Name of the JS file in "public_html/js/" directory without extension.
 *
 * @return void
 */
function phb_insert_js($filename)
{
    $filename = phb_add_timestamp("js/$filename.js");

    if (!$filename) {
        echo "<script src=\"/$filename\"></script>";
    }
}

/**
 * Dump data and die
 *
 * @param mixed $data Data to dump.
 * @param bool $die Stop script execution or not.
 *
 * @return void
 */
function phb_dump($data, $die = false)
{
    echo '<pre>';
    var_dump($data);
    echo '</pre>';

    if ($die) {
        die();
    }
}

/**
 * Show array preview fo debugging
 *
 * @param array $array Array to print.
 *
 * @return void
 */
function phb_preview_array($array)
{
    echo '<pre>';
    print_r($array);
    echo '</pre>';
}

/**
 * Get image resolution
 *
 * Useful for PhotoSwipe JavaScript gallery.
 *
 * @param string $filename Absolute path to image file.
 *
 * @return string|false Image resolution like "1920x1080" or FALSE if file not found.
 */
function phb_get_image_size($filename)
{
    $size = getimagesize($filename);

    if (!$size) {
        return false;
    }

    $result_size = $size[0] . 'x' . $size[1];

    return $result_size;
}
