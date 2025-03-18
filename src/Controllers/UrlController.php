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
            'url' => $url,
            'flash' => $resultMessages
        ];

        return $this->container->get(Twig::class)->render($this->response, 'urls/show.html.twig', $params);
    }

    public function createUrlAction(array $urlData): Response
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
            $this->container->get('flash')->addMessage('success', 'Страница уже существует');
            return $this->response->withRedirect($this->router->urlFor('urls.show', ['id' => $url->getId()]), 302);
        }

        $id = $urlRepository->create($name, $createdAt);
        $this->container->get('flash')->addMessage('success', 'Страница успешно добавлена');
        return $this->response->withRedirect($this->router->urlFor('urls.show', ['id' => $id]), 302);
    }

    public function getUrlsAction(): Response
    {
        /** @var UrlRepository $urlRepository */
        $urlRepository = $this->container->get(UrlRepository::class);
        $urls = $urlRepository->getUrls();

        return $this->container->get(Twig::class)->render($this->response, 'urls/index.html.twig', ['urls' => $urls]);
    }
}
