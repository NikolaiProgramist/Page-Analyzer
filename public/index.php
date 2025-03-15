<?php

require_once __DIR__ . "/../vendor/autoload.php";

use DI\Container;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;

$container = new Container();
$container->set(Twig::class, function () {
    return Twig::create(__DIR__ . '/../src/templates');
});

$app = AppFactory::createFromContainer($container);
$app->add(TwigMiddleware::create($app, $container->get(Twig::class)));

$app->get('/', function (Request $request, Response $response): Response {
    return $this->get(Twig::class)->render($response, 'index.html.twig');
});

$app->run();
