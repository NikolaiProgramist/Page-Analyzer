<?php

namespace Page\Analyzer\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Http\Response as Response;
use Psr\Http\Message\ResponseInterface;
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

class UrlController extends BaseController
{
    public function indexAction(Request $request, Response $response): ResponseInterface
    {
        return $this->view->render($response, 'index.html.twig', ['page' => 'index']);
    }

    public function showAction(Request $request, Response $response, array $args): ResponseInterface
    {
        $urlId = (int) $args['id'];

        $url = $this->urlRepository->getById($urlId);
        $checks = $this->checkRepository->getByUrlId($urlId);

        if (!$url) {
            return $this->view->render($response->withStatus(404), '404.html.twig');
        }

        $messages = $this->flash->getMessages();

        $params = [
            'url' => $url,
            'checks' => $checks,
            'flash' => $messages
        ];

        return $this->view->render($response, 'urls/show.html.twig', $params);
    }

    public function createAction(Request $request, Response $response): ResponseInterface
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

            return $this->view->render($response->withStatus(422), 'index.html.twig', $params);
        }

        preg_match('/^https?:\/\/[\w\-.]+\.\w{2,}/', $name, $domain);
        $url = $this->urlRepository->getByName($domain[0] ?? '');

        if ($url) {
            $id = (string) $url->getId();
            $this->flash->addMessage('success', 'Страница уже существует');
            return $response->withRedirect($this->router->urlFor('urls.show', ['id' => $id]), 302);
        }

        $id = (string) $this->urlRepository->create($domain[0] ?? '', $createdAt);
        $this->flash->addMessage('success', 'Страница успешно добавлена');
        return $response->withRedirect($this->router->urlFor('urls.show', ['id' => $id]), 302);
    }

    public function showAllAction(Request $request, Response $response): ResponseInterface
    {
        $urls = [];

        foreach ($this->urlRepository->getAll() as $row) {
            $url = new Url($row['name']);
            $url->setId($row['id']);
            $url->setCreatedAt($row['created_at']);

            $lastCreatedAt = $this->checkRepository->getLastCreatedAt($row['id']);
            $url->setLastCheck($lastCreatedAt);

            $lastStatusCode = $this->checkRepository->getLastStatusCode($row['id']);
            $url->setLastStatusCode($lastStatusCode);

            $urls[] = $url;
        }

        $params = [
            'page' => 'urls.index',
            'urls' => $urls
        ];

        return $this->view->render($response, 'urls/index.html.twig', $params);
    }

    public function checkAction(Request $request, Response $response, array $args): ResponseInterface
    {
        $urlId = $args['url_id'];
        $createdAt = Carbon::now();

        $url = $this->urlRepository->getById($urlId);

        if (is_null($url)) {
            return $this->view->render($response->withStatus(404), '404.html.twig');
        }

        $name = $url->getName();

        try {
            $client = new Client();
            $guzzleResponse = $client->request('GET', $name);

            $status = $guzzleResponse->getStatusCode();
            $document = new Document($guzzleResponse->getBody()->getContents());

            $this->flash->addMessage('success', 'Страница успешно проверена');
        } catch (ConnectException | TooManyRedirectsException) {
            $status = false;
            $document = new Document();

            $this->flash->addMessage('danger', 'Произошла ошибка при проверке, не удалось подключиться');
        } catch (ClientException | ServerException $exception) {
            $status = $exception->getResponse()->getStatusCode();
            $document = new Document($exception->getResponse()->getBody()->getContents());

            $this->flash->addMessage('success', 'Страница успешно проверена');
        }

        $h1 = optional($document->first('h1'))->text();
        $title = optional($document->first('title'))->text();

        /** @var DOMElement $domElement */
        $domElement = optional($document->first('meta[name=description]'));
        $description = $domElement->getAttribute('content');

        if ($status) {
            $this->checkRepository->create($urlId, $status, $h1, $title, $description, $createdAt);
        }

        return $response->withRedirect($this->router->urlFor('urls.show', ['id' => $urlId]), 302);
    }
}
