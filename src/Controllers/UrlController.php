<?php

namespace Page\Analyzer\Controllers;

use Exception;
use DI\Container;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Interfaces\RouteParserInterface;
use Slim\Views\Twig;
use Carbon\Carbon;
use Valitron\Validator;
use GuzzleHttp\Client;
use DiDom\Document;
use Page\Analyzer\DAO\Url;
use Page\Analyzer\Repositories\UrlRepository;
use Page\Analyzer\Repositories\CheckRepository;

class UrlController
{
    private Response $response;
    private Container $container;
    private ?RouteParserInterface $router;

    public function __construct(Response $response, Container $container, ?RouteParserInterface $router = null)
    {
        $this->response = $response;
        $this->container = $container;
        $this->router = $router;
    }

    public function showAction(int $id): Response
    {
        /** @var UrlRepository $urlRepository */
        $urlRepository = $this->container->get(UrlRepository::class);

        /** @var CheckRepository $checkRepository */
        $checkRepository = $this->container->get(CheckRepository::class);

        $url = $urlRepository->getById($id);
        $checks = $checkRepository->getByUrlId($id);

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
            'checks' => $checks,
            'flash' => $resultMessages
        ];

        return $this->container->get(Twig::class)->render($this->response, 'urls/show.html.twig', $params);
    }

    public function createAction(array $urlData): Response
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
        $url = $urlRepository->getByName($name);

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

    public function showAllAction(): Response
    {
        /** @var UrlRepository $urlRepository */
        $urlRepository = $this->container->get(UrlRepository::class);
        $params = [
            'page' => 'urls',
            'urls' => $urlRepository->getAll()
        ];

        return $this->container->get(Twig::class)->render($this->response, 'urls/index.html.twig', $params);
    }

    public function checkAction(int $id): Response
    {
        /** @var UrlRepository $urlRepository */
        $urlRepository = $this->container->get(UrlRepository::class);

        $client = new Client();
        $name = $urlRepository->getById($id)->getName();

        try {
            $response = $client->request('GET', $name);
        } catch (Exception $m) {
            $this->container->get('flash')
                ->addMessage('danger', 'Произошла ошибка при проверке, не удалось подключиться');

            return $this->response->withRedirect($this->router->urlFor('urls.show', ['id' => $id]), 302);
        }

        $status = $response->getStatusCode();
        $body = $response->getBody();
        $createdAt = Carbon::now();

        $document = new Document($body->getContents());
        $h1 = optional($document->find('h1')[0] ?? '')->text();
        $title = optional($document->find('title')[0] ?? '')->text();
        $description = optional(
            $document->find('meta[name=description]')[0] ?? ''
        )->getAttribute('content');

        /** @var CheckRepository $checkRepository */
        $checkRepository = $this->container->get(CheckRepository::class);
        $checkRepository->create($id, $status, $h1, $title, $description, $createdAt);

        $this->container->get('flash')->addMessage('success', 'Страница успешно проверена');
        return $this->response->withRedirect($this->router->urlFor('urls.show', ['id' => $id]), 302);
    }
}
