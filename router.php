<?php
/**
 * `php -S` 用ルータ。実ファイル/ディレクトリでなければ index.php に転送する
 * （Apache の .htaccess のRewriteルールに相当）。開発サーバ専用、本番では使わない。
 *
 * 起動時は必ず `-t public` を付けること。
 *   php -S 127.0.0.1:8080 -t public router.php
 * 付けないと、下の `return false`（静的ファイルはPHPを通さず配信）の探索先が
 * public/ ではなくプロジェクト直下になり、画像やfaviconが404になる。
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
