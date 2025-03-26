<?php

require_once __DIR__ . "/../vendor/autoload.php";

use DI\Container;
use Psr\Container\ContainerInterface;
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
    return Twig::create(__DIR__ . '/../src/Templates');
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
    $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $connection;
});

$sql = file_get_contents(implode('/', [__DIR__, '../database.sql']));
$container->get(PDO::class)->exec($sql);

$container->set('flash', fn () => new Messages());

$app = AppFactory::createFromContainer($container);
$app->add(TwigMiddleware::create($app, $container->get(Twig::class)));

$router = $app->getRouteCollector()->getRouteParser();
$container->set(RouteParserInterface::class, $router);

$container->set(UrlController::class, fn (ContainerInterface $container) => new UrlController($container));

$errorMiddleware = $app->addErrorMiddleware(true, true, true);
$errorMiddleware->setErrorHandler(HttpNotFoundException::class, function () use ($app) {
    $response = $app->getResponseFactory()->createResponse();

    /** @var Response $response */
    return $this->get(Twig::class)->render($response->withStatus(404), '404.html.twig');
});

$app->get('/', [UrlController::class, 'indexAction'])->setName('index');

$app->post('/urls', [UrlController::class, 'createAction'])->setName('urls.create');

$app->get('/urls/{id}', [UrlController::class, 'showAction'])->setName('urls.show');

$app->get('/urls', [UrlController::class, 'showAllAction'])->setName('urls.index');

$app->post('/urls/{url_id}/checks', [UrlController::class, 'checkAction'])->setName('urls.checks.create');

$app->run();
