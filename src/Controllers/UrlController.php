<?php

namespace Page\Analyzer\Controllers;

use DI\Container;
use Page\Analyzer\DAO\Url;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Views\Twig;
use Carbon\Carbon;
use Valitron\Validator;
use Page\Analyzer\repositories\UrlRepository;

class UrlController
{
    public function __construct()
    {
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
