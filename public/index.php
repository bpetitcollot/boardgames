<?php

use App\Kernel;
use Symfony\Component\ErrorHandler\Debug;
use Symfony\Component\HttpFoundation\Request;

require dirname(__DIR__).'/config/bootstrap.php';

if ($_SERVER['APP_DEBUG']) {
    umask(0000);

    Debug::enable();
}

// Cloudflare reverse-proxy uses a custom header for client's IP
// https://support.cloudflare.com/hc/fr-fr/articles/200170986-Comment-Cloudflare-g%C3%A8re-les-en-t%C3%AAtes-de-requ%C3%AAtes-HTTP-
// https://symfony.com/doc/current/deployment/proxies.html#custom-headers-when-using-a-reverse-proxy
if (array_key_exists('CF-CONNECTING-IP', $_SERVER)){
    $_SERVER['HTTP_X_FORWARDED_FOR'] = $_SERVER['CF-CONNECTING-IP'];
}

if ($trustedProxies = $_SERVER['TRUSTED_PROXIES'] ?? $_ENV['TRUSTED_PROXIES'] ?? false) {
    Request::setTrustedProxies(explode(',', $trustedProxies), Request::HEADER_X_FORWARDED_ALL ^ Request::HEADER_X_FORWARDED_HOST);
}

if ($trustedHosts = $_SERVER['TRUSTED_HOSTS'] ?? $_ENV['TRUSTED_HOSTS'] ?? false) {
    Request::setTrustedHosts([$trustedHosts]);
}

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);