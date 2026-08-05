<?php

namespace Daniel\Origins\Tester;

use Daniel\Origins\HttpMethod;
use Daniel\Origins\ServerDispacher;
use Override;

class ServerDispacherTester extends ServerDispacher
{
    #[Override]
    public function map(): void
    {
        parent::map();

        ServerDispacher::$routes = array_values(array_filter(
            ServerDispacher::$routes,
            static fn($route) => !($route->path === '/' && $route->method === HttpMethod::GET)
        ));
    }
}
