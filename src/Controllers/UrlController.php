<?php

namespace Page\Analyzer\Controllers;

use DI\Container;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Interfaces\RouteParserInterface;
use Slim\Views\Twig;
use Carbon\Carbon;
use Valitron\Validator;
use Page\Analyzer\DAO\Url;
use Page\Analyzer\Repositories\UrlRepository;

class UrlController
{
    private Response $response;
    private Container $container;
    private ?RouteParserInterface $router;

    public function __construct(Response $response, Container $container, RouteParserInterface $router = null)
    {
        $this->response = $response;
        $this->container = $container;
        $this->router = $router;
    }

    public function getUrlAction(int $id): Response
    {
        /** @var UrlRepository $urlRepository */
        $urlRepository = $this->container->get(UrlRepository::class);
        $url = $urlRepository->getUrlById($id);

        if (!$url) {
            return $this->container->get(Twig::class)->render($this->response->withStatus(404), '404.html.twig');
        }

        $flashMessages = $this->container->get('flash')->getMessages();
        $resultMessages = array_reduce(array_keys($flashMessages), function ($messages, $status) use ($flashMessages) {
            $messages[] = ['status' => $status, 'text' => $flashMessages[$status][0]];
            return $messages;
        });

        $params = [
            'page' => 'urls',
            'url' => $url,
            'flash' => $resultMessages
        ];

        return $this->container->get(Twig::class)->render($this->response, 'urls/show.html.twig', $params);
    }

    public function createUrlAction(array $urlData): Response
    {
        ['name' => $name] = $urlData;
        $createdAt = Carbon::now();

        $validator = new Validator(['url' => $name]);
        $validator->setPrependLabels(false);

        $validator->rule(fn ($field, $value) => strlen($value) > 0, 'url')->message('URL не должен быть пустым');

        $validator->rule(
            function ($field, $value) {
                preg_match('/^https?:\/\/[a-z0-9-.]+\.[a-z]{2,}$/', $value, $result);
                $length = strlen($value);
                return count($result) > 0 && $length <= 255;
            },
            'url'
        )->message('Некорректный URL');

        if (!$validator->validate()) {
            $message = $validator->errors()['url'][0];

            $params = [
                'page' => 'index',
                'url' => new Url($name),
                'errors' => [
                    'text' => $message
                ]
            ];

            return $this->container->get(Twig::class)->render(
                $this->response->withStatus(422),
                'index.html.twig',
                $params
            );
        }

        /** @var UrlRepository $urlRepository */
        $urlRepository = $this->container->get(UrlRepository::class);
        $url = $urlRepository->getUrlByName($name);

        if ($url) {
            $params = ['page' => 'urls', 'id' => $url->getId()];
            $this->container->get('flash')->addMessage('success', 'Страница уже существует');
            return $this->response->withRedirect($this->router->urlFor('urls.show', $params), 302);
        }

        $params = [
            'page' => 'urls',
            'id' => $urlRepository->create($name, $createdAt)
        ];

        $this->container->get('flash')->addMessage('success', 'Страница успешно добавлена');
        return $this->response->withRedirect($this->router->urlFor('urls.show', $params), 302);
    }

    public function getUrlsAction(): Response
    {
        /** @var UrlRepository $urlRepository */
        $urlRepository = $this->container->get(UrlRepository::class);
        $params = [
            'page' => 'urls',
            'urls' => $urlRepository->getUrls()
        ];

        return $this->container->get(Twig::class)->render($this->response, 'urls/index.html.twig', $params);
    }
}
