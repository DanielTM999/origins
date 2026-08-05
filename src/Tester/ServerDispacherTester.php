<?php

namespace Daniel\Origins\Tester;

use Daniel\Origins\HttpMethod;
use Daniel\Origins\ServerDispacher;
use Override;

/**
 * Dispatcher usado no boot de teste.
 *
 * Reaproveita todo o mapeamento do {@see ServerDispacher}, porém remove a rota raiz
 * (`/` no GET) para que a home da aplicação não seja iniciada/executada durante os testes.
 */
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
