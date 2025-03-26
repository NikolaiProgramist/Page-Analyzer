<?php

namespace Page\Analyzer\Controllers;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Http\Response as Response;
use Slim\Interfaces\RouteParserInterface;
use Slim\Views\Twig;
use Carbon\Carbon;
use Valitron\Validator;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Exception\TooManyRedirectsException;
use DOMElement;
use DiDom\Document;
use Page\Analyzer\DAO\Url;
use Page\Analyzer\Repositories\UrlRepository;
use Page\Analyzer\Repositories\CheckRepository;

class UrlController
{
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function indexAction(Request $request, Response $response): Response
    {
        return $this->container->get(Twig::class)->render($response, 'index.html.twig', ['page' => 'index']);
    }

    public function showAction(Request $request, Response $response, array $args): Response
    {
        $urlId = (int) $args['id'];

        /** @var UrlRepository $urlRepository */
        $urlRepository = $this->container->get(UrlRepository::class);

        /** @var CheckRepository $checkRepository */
        $checkRepository = $this->container->get(CheckRepository::class);

        $url = $urlRepository->getById($urlId);
        $checks = $checkRepository->getByUrlId($urlId);

        if (!$url) {
            return $this->container->get(Twig::class)->render($response->withStatus(404), '404.html.twig');
        }

        $messages = $this->container->get('flash')->getMessages();

        $params = [
            'url' => $url,
            'checks' => $checks,
            'flash' => $messages
        ];

        return $this->container->get(Twig::class)->render($response, 'urls/show.html.twig', $params);
    }

    public function createAction(Request $request, Response $response): Response
    {
        $urlData = ((array) $request->getParsedBody())['url'] ?? '';

        ['name' => $name] = $urlData;
        $createdAt = Carbon::now();

        $validator = new Validator(['url' => $name]);

        $validator->rule('required', ['url'])->message('URL не должен быть пустым');
        $validator->rule('lengthMax', ['url'], 255)->message('Некорректный URL');
        $validator->rule('url', ['url'])->message('Некорректный URL');

        if (!$validator->validate()) {
            $error = optional($validator->errors())['url'][0];

            $params = [
                'page' => 'index',
                'url' => new Url($name),
                'error' => $error
            ];

            return $this->container->get(Twig::class)->render(
                $response->withStatus(422),
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
            return $response->withRedirect(
                $this->container->get(RouteParserInterface::class)->urlFor('urls.show', ['id' => $id]),
                302
            );
        }

        $id = $urlRepository->create($name, $createdAt);
        $this->container->get('flash')->addMessage('success', 'Страница успешно добавлена');
        return $response->withRedirect(
            $this->container->get(RouteParserInterface::class)->urlFor('urls.show', ['id' => $id]),
            302
        );
    }

    public function showAllAction(Request $request, Response $response): Response
    {
        /** @var UrlRepository $urlRepository */
        $urlRepository = $this->container->get(UrlRepository::class);

        /** @var CheckRepository $checkRepository */
        $checkRepository = $this->container->get(CheckRepository::class);

        $urls = [];

        foreach ($urlRepository->getAll() as $row) {
            $url = new Url($row['name']);
            $url->setId($row['id']);
            $url->setCreatedAt($row['created_at']);

            $lastCreatedAt = $checkRepository->getLastCreatedAt($row['id']);
            $url->setLastCheck($lastCreatedAt);

            $lastStatusCode = $checkRepository->getLastStatusCode($row['id']);
            $url->setLastStatusCode($lastStatusCode);

            $urls[] = $url;
        }

        $params = [
            'page' => 'urls.index',
            'urls' => $urls
        ];

        return $this->container->get(Twig::class)->render($response, 'urls/index.html.twig', $params);
    }

    public function checkAction(Request $request, Response $response, array $args): Response
    {
        $urlId = $args['url_id'];
        $createdAt = Carbon::now();

        /** @var UrlRepository $urlRepository */
        $urlRepository = $this->container->get(UrlRepository::class);
        $url = $urlRepository->getById($urlId);

        if (is_null($url)) {
            return $this->container->get(Twig::class)->render($response->withStatus(404), '404.html.twig');
        }

        $name = $url->getName();

        try {
            $client = new Client();
            $guzzleResponse = $client->request('GET', $name);

            $status = $guzzleResponse->getStatusCode();
            $document = new Document($guzzleResponse->getBody()->getContents());

            $this->container->get('flash')
                ->addMessage('success', 'Страница успешно проверена');
        } catch (ConnectException | TooManyRedirectsException) {
            $status = false;
            $document = new Document();

            $this->container->get('flash')
                ->addMessage('danger', 'Произошла ошибка при проверке, не удалось подключиться');
        } catch (ClientException | ServerException $exception) {
            $status = $exception->getResponse()->getStatusCode();
            $document = new Document($exception->getResponse()->getBody()->getContents());

            $this->container->get('flash')
                ->addMessage('success', 'Страница успешно проверена');
        }

        $h1 = optional($document->first('h1'))->text();
        $title = optional($document->first('title'))->text();

        /** @var DOMElement $domElement */
        $domElement = optional($document->first('meta[name=description]'));
        $description = $domElement->getAttribute('content');

        if ($status) {
            $this->container->get(CheckRepository::class)
                ->create($urlId, $status, $h1, $title, $description, $createdAt);
        }

        return $response->withRedirect(
            $this->container->get(RouteParserInterface::class)->urlFor('urls.show', ['id' => $urlId]),
            302
        );
    }
}
