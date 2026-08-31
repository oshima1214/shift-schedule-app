<?php
/**
 * `php -S` 用ルータ。実ファイル/ディレクトリでなければ index.php に転送する
 * （Apache の .htaccess のRewriteルールに相当）。開発サーバ専用、本番では使わない。
 */
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$file = __DIR__.'/public'.$uri;

if ($uri !== '/' && file_exists($file) && ! is_dir($file))
{
	return false;
}

chdir(__DIR__.'/public');
$_SERVER['SCRIPT_NAME'] = '/index.php';
require __DIR__.'/public/index.php';
