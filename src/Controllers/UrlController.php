<?php

namespace Hexlet\Code\Controllers;

use Exception;
use DI\Container;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Views\Twig;
use Carbon\Carbon;
use Valitron\Validator;
use Hexlet\Code\repositories\UrlRepository;

class UrlController
{
    public function __construct()
    {
    }

    public static function createUrlAction(Response $response, Container $container, array $urlData): Response
    {
        ['name' => $name] = $urlData;
        $createdAt = Carbon::now();

        $validator = new Validator(['url' => $name]);
        $validator->rule('required', 'url');
        $validator->rule('url', 'url');

        if (!$validator->validate()) {
            return $response->withStatus(422)->write('Incorrect URL');
        }

        /** @var UrlRepository $urlRepository */
        $urlRepository = $container->get(UrlRepository::class);

        if ($urlRepository->getUrlByName($name)) {
            return $response->withStatus(208)->write('This url already exists');
        }

        $urlRepository->create($name, $createdAt);
        return $container->get(Twig::class)->render($response, 'index.html.twig');
    }
}