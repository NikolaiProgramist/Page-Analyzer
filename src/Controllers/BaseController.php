<?php

namespace Page\Analyzer\Controllers;

use Psr\Container\ContainerInterface;
use Slim\Views\Twig;
use Slim\Flash\Messages;
use Slim\Interfaces\RouteParserInterface;
use Page\Analyzer\Repositories\UrlRepository;
use Page\Analyzer\Repositories\CheckRepository;

class BaseController
{
    protected ContainerInterface $container;
    protected Twig $view;
    protected Messages $flash;
    protected RouteParserInterface $router;
    protected UrlRepository $urlRepository;
    protected CheckRepository $checkRepository;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

        $this->view = $this->container->get(Twig::class);
        $this->flash = $this->container->get(Messages::class);
        $this->router = $this->container->get(RouteParserInterface::class);
        $this->urlRepository = $this->container->get(UrlRepository::class);
        $this->checkRepository = $this->container->get(CheckRepository::class);
    }
}
