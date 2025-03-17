<?php

require_once __DIR__ . "/../vendor/autoload.php";

use DI\Container;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;
use Slim\Flash\Messages;
use Page\Analyzer\Controllers\UrlController;

date_default_timezone_set('Europe/Moscow');
session_start();

$container = new Container();

$container->set(Twig::class, function () {
    return Twig::create(__DIR__ . '/../src/Templates');
});

$container->set(PDO::class, function () {
    $databaseUrl = parse_url(getenv('DATABASE_URL'));

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

$sql = file_get_contents(realpath(implode('/', [__DIR__, '../database.sql'])));
$container->get(PDO::class)->exec($sql);

$container->set('flash', fn () => new Messages());

$app = AppFactory::createFromContainer($container);
$app->add(TwigMiddleware::create($app, $container->get(Twig::class)));

$router = $app->getRouteCollector()->getRouteParser();

$app->get('/', function (Request $request, Response $response): Response {
    return $this->get(Twig::class)->render($response, 'index.html.twig');
});

$app->post('/urls', function (Request $request, Response $response) use ($router): Response {
    $urlData = $request->getParsedBody()['url'];
    return UrlController::createUrlAction($response, $this, $router, $urlData);
})->setName('urls.create');

$app->get('/urls/{id}', function (Request $request, Response $response, array $args): Response {
    $urlId = $args['id'];
    return UrlController::getUrlAction($response, $this, $urlId);
})->setName('urls.show');

$app->run();
