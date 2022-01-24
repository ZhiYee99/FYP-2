<?php
define('tpwatchshop', true);

$base_url = isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] === 'on' || $_SERVER['HTTPS'] === 1) || isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https' ? 'https' : 'http';
$base_url .= '://' . rtrim($_SERVER['HTTP_HOST'], '/');
$base_url .= $_SERVER['SERVER_PORT'] == 80 || $_SERVER['SERVER_PORT'] == 443 ? '' : ':' . $_SERVER['SERVER_PORT'];
$base_url .= '/' . ltrim(substr(str_replace('\\', '/', realpath(__DIR__)), strlen($_SERVER['DOCUMENT_ROOT'])), '/');
define('base_url', rtrim($base_url, '/') . '/');
session_start();
include 'config.php';
include 'functions.php';
$pdo = database();
$error = '';
$url = routes([
    '/',
    '/home',
    '/product/{id}',
    '/products',
    '/products/{category}/{sort}',
    '/products/{p}/{category}/{sort}',
    '/myaccount',
    '/cart',
    '/cart/{remove}',
    '/checkout',
    '/placeorder',
    '/search/{query}',
    '/logout'
]);


if ($url) {
    include $url;
} else {
    $page = isset($_GET['page']) && file_exists($_GET['page'] . '.php') ? $_GET['page'] : 'home';
    include $page . '.php';
}
?>
