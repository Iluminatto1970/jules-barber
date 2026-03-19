<?php

function renderPhpFile($file)
{
    $currentDir = getcwd();
    chdir(dirname($file));
    require $file;
    chdir($currentDir);
}

$requestUri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
$uri = parse_url($requestUri, PHP_URL_PATH);
$uri = rawurldecode($uri ?: '/');
$query = parse_url($requestUri, PHP_URL_QUERY);

if (strpos($uri, '..') !== false) {
    http_response_code(400);
    echo '400 Bad Request';
    return true;
}

$path = __DIR__ . $uri;

if ($uri !== '/' && file_exists($path) && !is_dir($path)) {
    return false;
}

if (is_dir($path)) {
	if ($uri !== '/' && substr($uri, -1) !== '/') {
		$location = $uri . '/';
		if (!empty($query)) {
			$location .= '?' . $query;
		}

		header('Location: ' . $location, true, 301);
		return true;
	}

    $index = rtrim($path, '/') . '/index.php';
    if (file_exists($index)) {
        renderPhpFile($index);
        return true;
    }
}

$route = trim($uri, '/');

if ($route === '') {
    renderPhpFile(__DIR__ . '/index.php');
    return true;
}

$phpFile = __DIR__ . '/' . $route . '.php';
if (file_exists($phpFile)) {
    renderPhpFile($phpFile);
    return true;
}

http_response_code(404);
echo '404 Not Found';
