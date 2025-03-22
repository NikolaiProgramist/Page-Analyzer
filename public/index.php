<?php

require_once __DIR__ . "/../vendor/autoload.php";

use DI\Container;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Http\Response as Response;
use Slim\Exception\HttpNotFoundException;
use Slim\Interfaces\RouteParserInterface;
use Slim\Factory\AppFactory;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;
use Slim\Flash\Messages;
use Page\Analyzer\Controllers\UrlController;

error_reporting(E_ALL & ~E_DEPRECATED);
date_default_timezone_set('Europe/Moscow');
session_start();

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->safeLoad();

$container = new Container();

$container->set(Twig::class, function () {
    return Twig::create(__DIR__ . '/Templates');
});

$container->set(PDO::class, function () {
    $databaseUrl = parse_url($_ENV['DATABASE_URL']);

    $host = $databaseUrl['host'];
    $port = $databaseUrl['port'];
    $username = $databaseUrl['user'];
    $password = $databaseUrl['pass'];
    $dbname = ltrim($databaseUrl['path'], '/');

    $dsn = "pgsql:host={$host};port={$port};dbname={$dbname};user={$username};password={$password}";
    $connection = new PDO($dsn);
    $connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    return $connection;
});

$sql = file_get_contents(implode('/', [__DIR__, '../database.sql']));
$container->get(PDO::class)->exec($sql);

$container->set('flash', fn () => new Messages());

$app = AppFactory::createFromContainer($container);
$app->add(TwigMiddleware::create($app, $container->get(Twig::class)));

$router = $app->getRouteCollector()->getRouteParser();

$errorMiddleware = $app->addErrorMiddleware(true, true, true);
$errorMiddleware->setErrorHandler(HttpNotFoundException::class, function () use ($app, $router) {
    $response = $app->getResponseFactory()->createResponse();

    /** @var Response $response */
    return $response->withRedirect($router->urlFor('404'), 302);
});

$app->get('/', function (Request $request, Response $response): Response {
    return $this->get(Twig::class)->render($response, 'index.html.twig');
})->setName('index');

$app->post('/urls', function (Request $request, Response $response) use ($router): Response {
    $urlData = $request->getParsedBody()['url'];
    return (new UrlController($response, $this, $router))->createAction($urlData);
})->setName('urls.create');

$app->get('/urls/{id}', function (Request $request, Response $response, array $args) use ($router): Response {
    $urlId = $args['id'];

    if (!is_numeric($urlId)) {
        return $response->withRedirect($router->urlFor('404'), 302);
    }

    return (new UrlController($response, $this, $router))->showAction($urlId);
})->setName('urls.show');

$app->get('/urls', function (Request $request, Response $response): Response {
    return (new UrlController($response, $this))->showAllAction();
})->setName('urls.index');

$app->post(
    '/urls/{url_id}/checks',
    function (Request $request, Response $response, array $args) use ($router): Response {
        $urlId = $args['url_id'];
        return (new UrlController($response, $this, $router))->checkAction($urlId);
    }
)->setName('urls.checks.create');

$app->get('/404', function (Request $request, Response $response) {
    return $this->get(Twig::class)->render($response->withStatus(404), '404.html.twig');
})->setName('404');

$app->run();
