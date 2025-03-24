<?php

namespace Page\Analyzer\Controllers;

use DI\Container;
use GuzzleHttp\Exception\GuzzleException;
use Slim\Http\Response as Response;
use Slim\Interfaces\RouteParserInterface;
use Slim\Views\Twig;
use Carbon\Carbon;
use Valitron\Validator;
use GuzzleHttp\Client;
use DOMElement;
use DiDom\Document;
use Page\Analyzer\DAO\Url;
use Page\Analyzer\Repositories\UrlRepository;
use Page\Analyzer\Repositories\CheckRepository;

class UrlController
{
    private Response $response;
    private Container $container;

    public function __construct(Response $response, Container $container)
    {
        $this->response = $response;
        $this->container = $container;
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
            return $this->response
                ->withRedirect(
                    $this->container->get(RouteParserInterface::class)->urlFor('404'),
                    302
                );
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

        $validator->rule('required', ['url'])->message('URL не должен быть пустым');
        $validator->rule('lengthMax', ['url'], 255)->message('Некорректный URL');
        $validator->rule('url', ['url'])->message('Некорректный URL');

        if (!$validator->validate() && is_array($validator->errors())) {
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
            $id = $url->getId();
            $this->container->get('flash')->addMessage('success', 'Страница уже существует');
            return $this->response
                ->withRedirect(
                    $this->container->get(RouteParserInterface::class)->urlFor('urls.show', ['id' => $id]),
                    302
                );
        }

        $id = $urlRepository->create($name, $createdAt);
        $this->container->get('flash')->addMessage('success', 'Страница успешно добавлена');
        return $this->response
            ->withRedirect(
                $this->container->get(RouteParserInterface::class)->urlFor('urls.show', ['id' => $id]),
                302
            );
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
        $url = $urlRepository->getById($id);

        if (is_null($url)) {
            return $this->response
                ->withRedirect(
                    $this->container->get(RouteParserInterface::class)->urlFor('404'),
                    302
                );
        }

        $name = $url->getName();

        try {
            $client = new Client();
            $response = $client->request('GET', $name);
        } catch (GuzzleException) {
            $this->container->get('flash')
                ->addMessage('danger', 'Произошла ошибка при проверке, не удалось подключиться');

            return $this->response
                ->withRedirect(
                    $this->container->get(RouteParserInterface::class)->urlFor('urls.show', ['id' => $id]),
                    302
                );
        }

        $status = $response->getStatusCode();
        $body = $response->getBody();
        $createdAt = Carbon::now();

        $document = new Document($body->getContents());
        $h1 = optional($document->first('h1'))->text();
        $title = optional($document->first('title'))->text();

        /** @var DOMElement $domElement */
        $domElement = optional($document->first('meta[name=description]'));
        $description = $domElement->getAttribute('content');

        /** @var CheckRepository $checkRepository */
        $checkRepository = $this->container->get(CheckRepository::class);
        $checkRepository->create($id, $status, $h1, $title, $description, $createdAt);

        $this->container->get('flash')->addMessage('success', 'Страница успешно проверена');
        return $this->response
            ->withRedirect(
                $this->container->get(RouteParserInterface::class)->urlFor('urls.show', ['id' => $id]),
                302
            );
    }
}
