<?php

require_once __DIR__ . "/../vendor/autoload.php";

use DI\Container;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;
use Carbon\Carbon;
use Hexlet\Code\repositories\UrlRepository;

$container = new Container();

$container->set(Twig::class, function () {
    return Twig::create(__DIR__ . '/../src/templates');
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

$app = AppFactory::createFromContainer($container);
$app->add(TwigMiddleware::create($app, $container->get(Twig::class)));

$app->get('/', function (Request $request, Response $response): Response {
    return $this->get(Twig::class)->render($response, 'index.html.twig');
});

$app->post('/urls', function (Request $request, Response $response): Response {
    $urlName = $request->getParsedBody()['url'];
    $createdAt = Carbon::now();

    /** @var UrlRepository $urlRepository */
    $urlRepository = $this->get(UrlRepository::class);

    try {
        $urlRepository->create($urlName['name'], $createdAt);
        return $this->get(Twig::class)->render($response, 'index.html.twig');
    } catch (Exception $e) {
        return $response->withStatus(404)->write('This url already exists');
    }
})->setName('urls.create');

$app->run();
