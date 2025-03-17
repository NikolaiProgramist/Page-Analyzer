<?php

namespace Page\Analyzer\Controllers;

use DI\Container;
use Page\Analyzer\DAO\Url;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Views\Twig;
use Carbon\Carbon;
use Valitron\Validator;
use Page\Analyzer\Repositories\UrlRepository;

class UrlController
{
    public function __construct()
    {
    }

    public static function getUrlAction(Response $response, Container $container, int $id): Response
    {
        /** @var UrlRepository $urlRepository */
        $urlRepository = $container->get(UrlRepository::class);
        $url = $urlRepository->getUrlById($id);

        if (!$url) {
            return $container->get(Twig::class)->render($response->withStatus(404), '404.html.twig');
        }

        return $container->get(Twig::class)->render($response, 'urls/show.html.twig', ['url' => $url]);
    }

    public static function createUrlAction(Response $response, Container $container, array $urlData): Response
    {
        ['name' => $name] = $urlData;
        $createdAt = Carbon::now();

        Validator::lang('ru');
        $validator = new Validator(['url' => $name]);
        // $validator->setPrependLabels(false);
        $validator->rule('required', 'url');
        $validator->rule('url', 'url');
        $validator->rule('lengthMax', 'url', 255);

        if (!$validator->validate()) {
            $message = ucfirst($validator->errors()['url'][0]);

            $params = [
                'url' => new Url($name),
                'errors' => [
                    'name' => $message
                ]
            ];

            return $container->get(Twig::class)->render(
                $response->withStatus(422),
                'index.html.twig',
                $params
            );
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
