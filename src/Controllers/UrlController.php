<?php

namespace Page\Analyzer\Controllers;

use DI\Container;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Views\Twig;
use Carbon\Carbon;
use Valitron\Validator;
use Page\Analyzer\DAO\Url;
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

    public static function createUrlAction(Response $response, Container $container, $router, array $urlData): Response
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
        $url = $urlRepository->getUrlByName($name);

        if ($url) {
            return $response->withRedirect($router->urlFor('urls.show', ['id' => $url->getId()]), 302);
        }

        $id = $urlRepository->create($name, $createdAt);
        return $response->withRedirect($router->urlFor('urls.show', ['id' => $id]), 302);
    }
}
