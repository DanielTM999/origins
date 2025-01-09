# languages

- [Português (PT)](#documentação-do-framework-php-origins)
- [English (EN)](#framework-documentation-php-origins)


# Framework Documentation PHP Origins

## Index

- [Introduction](#introduction)
- [Installation](#installation)
- [Main Requirements](#main-requirements)
- [Key Concepts](#key-concepts)
- [Basic Usage](#basic-usage)
- [Middleware](#middleware)
  - [Example](#example)
  - [Middleware Priority](#middleware-priority)
- [Initial Configuration](#initial-configuration)
- [Aspect-Oriented Programming (AOP)](#aspect-oriented-programming-aop)
- [Controller Advice](#controller-advice)
  - [Example](#example-1)
- [Log](#log)
  - [Usage Example](#usage-example)
- [View Rendering](#view-rendering)
- [Path Variables](#path-variables)
  - [Example](#example-2)
- [Controller Code Example](#controller-code-example)
- [Customizing Configuration (Optional)](#customizing-configuration-optional)
- [Threads with the `Thread` Class](#threads-with-the-thread-class)
  - [Implementing the `Runnable` Interface](#implementing-the-runnable-interface)
  - [Using the `Thread` Class](#using-the-thread-class)
  - [Available Methods in the `Thread` Class](#available-methods-in-the-thread-class)
  - [Technical Details](#technical-details)
  - [Benefits](#benefits)

## Introduction

Origins is a minimalist PHP framework designed to simplify web application development. It provides a flexible and scalable structure for efficiently building web applications.

## Installation

To start using the Origins framework, follow these simple steps:

1. Clone the Origins repository into your development environment:

    ```bash
    git clone https://github.com/DanielTM999/origins.git
    ```

2. Install Composer dependencies:

    ```bash
    composer require danieltm/origins
    ```

3. Configure your web server to route requests to the framework's `public` directory.

## Main Requirements

PHP 8.0

## Key Concepts

The Origins framework is built upon a few core concepts:

- **Controller**: Classes marked with the `Controller` attribute containing methods corresponding to API endpoints.

- **Dependency Injection**: Automatically manages class creation and injects necessary dependencies.

- **Routing Attributes**: The `Get`, `Post`, `Delete`, and `Put` attributes map controller methods to API endpoints.

- **Middleware**: Classes that intercept requests before they reach controllers, enabling logic implementation like authentication or logging.

- **Controller Advice**: Enables centralized and customized error handling for exceptions during request execution.

- **Aspect-Oriented Programming (AOP)**: Uses aspects to implement cross-cutting concerns such as logging and security.

- **Log**: An integrated system for logging events and errors, used for monitoring and debugging.

- **View Rendering**: Supports rendering pages with dynamic data models.

## Basic Usage

To create a web application using the Origins framework, follow these steps:

1. **Define Your Controllers**: Create Controller classes and mark them with the `Controller` attribute.

2. **Define Endpoints**: Use the `Get`, `Post`, `Delete`, and `Put` attributes to map controller methods to API endpoints.

3. **Initialize the Framework**: Create an instance of `Origin` and call the `run()` method to start routing and dispatching requests.

## Middleware

Middlewares allow request interception and manipulation before they reach controllers. A common use case is logging or security validations.

To create a middleware, extend the `Middleware` class and implement the `onPerrequest` method. The framework ensures that middleware is executed before controllers are triggered.

### Example

A middleware for logging requests:

```php
final class IpFilter extends Middleware
{
    #[Override]
    public function onPerrequest(Request $req): void
    {
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $method = $_SERVER['REQUEST_METHOD'];

        Log::info("$path: $method", "logs/requests.log");

        if ($method === "POST") {
            $body = json_encode($req->getBody());
            Log::info("$path ==> body: $body", "logs/requests.log");
        }
    }
}
```

### Middleware Priority

The `FilterPriority` attribute can be used to set the execution priority of middlewares. Higher-priority middlewares (numerically larger values) are executed first.

```php
use Daniel\Origins\FilterPriority;

#[FilterPriority(10)]
final class HighPriorityFilter extends Middleware
{
    // Middleware Logic
}

#[FilterPriority(1)]
final class LowPriorityFilter extends Middleware
{
    // Middleware Logic
}
```

## Initial Configuration

Create a `.htaccess` file in the root of your project to route requests. This serves as the request engine:

```.htaccess
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [L]
```

## Aspect-Oriented Programming (AOP)

Aspect-Oriented Programming allows the implementation of cross-cutting concerns such as logging and security in a modular and reusable way.

### Note

**Aspect-Oriented Programming (AOP)** is an advanced framework feature primarily used to handle cross-cutting concerns, such as logging and security. **While AOP does not directly modify controllers**, it can alter the behavior of methods, their arguments, and even their return values. Since AOP manipulates memory references rather than clones, it can directly impact the state of objects, making it a critical area. Due to this potential impact, it is essential to handle it carefully, as unexpected changes in the execution flow can affect other parts of the system that rely on these references.



### Aspect Class

The `Aspect` class provides a way to execute logic before a controller method is executed. This can be used for cross-cutting concerns like logging, security, and data transformation.

```php
<?php

namespace Daniel\Origins;

use ReflectionMethod;

abstract class Aspect
{
    public function __construct() {}
    abstract public function aspectBefore(object &$controllerEntity, ReflectionMethod &$method, array &$varArgs);
}
?>
```

To create a custom aspect, extend the `Aspect` class and implement the `aspectBefore` method:

```php
<?php

namespace App\Aspects;

use Daniel\Origins\Aspect;
use ReflectionMethod;

class LoggingAspect extends Aspect
{
    public function aspectBefore(object &$controllerEntity, ReflectionMethod &$method, array &$varArgs)
    {
        error_log("Executing method: " . $method->getName());
    }
}
?>
```

This ensures that custom logic runs before the execution of controller methods, allowing for greater modularity and maintainability.

## Controller Advice

Controller Advice enables centralized and organized exception handling.

To use, extend the `ControllerAdvice` class and override the `onError` method. Implement specific handling for different exception types.

### Example

Managing authentication and permissions:

```php
class AuthExceptionHandler extends ControllerAdvice
{
    #[Override]
    public function onError(Throwable $exception): void
    {
        if ($exception instanceof AuthorizationException) {
            header("Location: /login");
            exit;
        }

        if ($exception instanceof AuthorityAuthorizationException) {
            echo "You do not have permission to access this page.";
            exit;
        }

        echo "Internal server error.";
        exit;
    }
}
```

## Log

The integrated logging system allows recording events in files for monitoring or auditing. Use the `Log` class to record information, warnings, or errors.

### Usage Example

```php
Log::info("User successfully logged in", "app.log");
Log::warning("Unauthorized access attempt", "security.log");
Log::error("Unexpected error processing request", "errors.log");
```

## View Rendering

The framework allows rendering dynamic pages with data. Use the `renderPage` method to pass a data model to a view.

```php
// in the controller
renderPage("index.php", ["number" => rand()]);

// in index.php
global $model;
echo $model["number"];
```

## Path Variables

Endpoints can contain path variables, like `{id}`. Use the `getPathVar` method to retrieve these variables as an associative array.

### Example

```php
$variables = $this->getPathVar();
$id = $variables['id'] ?? null;
```

## Controller Code Example

Here is an example of creating a Controller and mapping a method to an endpoint:

```php
<?php

use Daniel\Origins\Controller;
use Daniel\Origins\Get;
use Daniel\Origins\Request;

#[Controller]
class UserController
{

    #[Inject]
    private Service $service;

    #[Get('/users')]
    public function getAllUsers()
    {
        // Logic
    }

    #[Get("/")]
    public function index(Request $req){
        $headers = $req->getHeaders();
        $body = $req->getBody();
    }   

    #[Get("/number")]
    public function getNumber(){
        echo $this->service->getNumber();
    }
}

// true for a single instance, false for per-request
#[Dependency(true)]
class Service{

    public function getNumber() : int{
        return rand();
    }
}

?>
```

## Controller Code Example

Here is an example of creating the index:

```php
<?php
    require "./vendor/autoload.php";
    use Daniel\Origins\Origin;

    $app = Origin::initialize();
    $app->run();

?>
```

## Customizing Configuration (Optional)

If you need to customize the framework configuration, extend the class:

- **`OnInit.php`**: This file contains an abstract class defining a method to configure the framework during initialization.

```php
<?php

class MyConfig extends OnInit{
    // Possible to inject dependencies during initialization but only for classes configured to start simultaneously

    #[Override]
    public function ConfigOnInit() : void{
        // Your config
    }
}

?>
```

## Threads with the `Thread` Class

The framework now supports running tasks in threads using the `Thread` class. This functionality is useful for executing long-running tasks in the background without blocking the main application execution.

### Implementing the `Runnable` Interface

To use the `Thread` class, implement the `Runnable` interface and define the `run` method, which will contain the task logic.

```php
namespace Daniel\Origins;

interface Runnable
{
    public function run();
}
```

### Using the `Thread` Class

The `Thread` class enables creating and managing threads simply. Here's an example:

```php
namespace Daniel\Origins;

final class ExampleTask implements Runnable
{
    public function run()
    {
        // Logic for the task to run in the background
        echo "Running task in thread...\n";
    }
}

$runnable = new ExampleTask();
$thread = new Thread($runnable);
$thread->start();

// Waits until the task is completed
$thread->waitUntilFinished();

echo "Task completed!\n";
```

### Available Methods in the `Thread` Class

- **`start()`**: Starts thread execution.
- **`isFinished(): bool`**: Checks if the task is completed.
- **`waitUntilFinished(): void`**: Blocks execution until the task is completed.

### Technical Details

1. The `Thread` class uses temporary files to manage thread execution state.
2. The execution command is automatically adjusted for Windows and Unix environments.
3. Temporary files are automatically removed after execution.

### Benefits

- Allows long-running tasks to execute in the background.
- Prevents blocking the main application execution.
- Easy to integrate with existing code.




# Documentação do Framework PHP Origins

## Índice

- [Introdução](#introdução)
- [Instalação](#instalação)
- [Principais Requisitos](#principais-requisitos)
- [Conceitos Principais](#conceitos-principais)
- [Uso Básico](#uso-básico)
- [Middleware](#middleware)
  - [Exemplo](#exemplo)
  - [Prioridade de Middleware](#prioridade-de-middleware)
- [Configuração Inicial](#configuração-inicial)
- [Programação Orientada a Aspectos (AOP)](#programação-orientada-a-aspectos-aop)
- [Controller Advice](#controller-advice)
  - [Exemplo](#exemplo-1)
- [Log](#log)
  - [Exemplo de Uso](#exemplo-de-uso)
- [Renderização de Views](#renderização-de-views)
- [Path Variables](#path-variables)
  - [Exemplo](#exemplo-2)
- [Exemplo de Código Controller](#exemplo-de-código-controller)
- [Personalizando a Configuração (Opcional)](#personalizando-a-configuração-opcional)
- [Threads com Classe `Thread`](#threads-com-classe-thread)
  - [Implementação da Interface `Runnable`](#implementação-da-interface-runnable)
  - [Utilização da Classe `Thread`](#utilização-da-classe-thread)
  - [Métodos Disponíveis na Classe `Thread`](#métodos-disponíveis-na-classe-thread)
  - [Detalhes Técnicos](#detalhes-técnicos)
  - [Benefícios](#benefícios)

## Introdução

Origins é um framework PHP minimalista desenvolvido para simplificar o desenvolvimento de aplicativos da web. Ele fornece uma estrutura flexível e escalável para construir aplicativos da web de forma eficiente.

## Instalação

Para começar a usar o framework Origins, siga estas etapas simples:

1. Clone o repositório do Origins em seu ambiente de desenvolvimento:

    ```bash
    git clone https://github.com/DanielTM999/origins.git
    ```

2. Instale as dependências do Composer:

    ```bash
    composer require danieltm/origins
    ```

3. Configure seu servidor web para direcionar as solicitações para o diretório `public` do framework.

## Principais Requisitos

PHP 8.0

## Conceitos Principais

O framework Origins é construído com base em alguns conceitos fundamentais:

- **Controller**: Classes marcadas com o atributo `Controller` que contêm métodos correspondentes a endpoints da API.

- **Dependency Injection (Injeção de Dependência)**: Gerencia automaticamente a criação e injeta dependências necessárias nas classes.

- **Atributos de Roteamento**: Os atributos `Get`, `Post`, `Delete` e `Put` mapeiam métodos de controllers para os endpoints da API.

- **Middleware**: Classes que interceptam solicitações antes de alcançarem os controllers, permitindo a implementação de lógicas específicas como autenticação ou logging.

- **Controller Advice**: Permite um tratamento centralizado e personalizado de exceções e erros que ocorrem durante a execução das requisições.

- **Programação Orientada a Aspectos (AOP)**: Usa aspectos para implementar preocupações transversais, como logging e segurança.

- **Log**: Sistema integrado para registro de eventos e erros, utilizado para monitoramento e depuração.

- **Renderização de Views**: Suporte para renderizar páginas com passagem de modelos de dados dinâmicos.

## Uso Básico

Para criar um aplicativo web usando o framework Origins, siga estas etapas:

1. **Defina seus Controllers**: Crie classes de Controller e marque-as com o atributo `Controller`.

2. **Defina os Endpoints**: Utilize os atributos `Get`, `Post`, `Delete` e `Put` para mapear os métodos dos Controllers para os endpoints da API.

3. **Inicie o Framework**: Crie uma instância do `Origin` e chame o método `run()` para iniciar o roteamento e despachar as solicitações.

## Middleware

Middlewares permitem a interceptação e manipulação de solicitações antes de alcançarem os controllers. Um exemplo comum de uso é o registro de logs ou validações de segurança.

Para criar um middleware, extenda a classe `Middleware` e implemente o método `onPerrequest`. O framework garante que o middleware seja executado antes que os controllers sejam acionados.

## Configuração Inicial

para iniciar crie um arquivo .htaccess na raiz do seu projeto, isso será o motor de direcionamento das requisições

```.htaccess

RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [L]

```

### Exemplo

Um middleware para registro de logs de solicitações:

```php
final class IpFilter extends Middleware
{
    #[Override]
    public function onPerrequest(Request $req): void
    {
        $caminho = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $metodo = $_SERVER['REQUEST_METHOD'];

        Log::info("$caminho: $metodo", "logs/requests.log");

        if ($metodo === "POST") {
            $corpo = json_encode($req->getBody());
            Log::info("$caminho ==> body: $corpo", "logs/requests.log");
        }
    }
}
```

### Prioridade de Middleware

O atributo `FilterPriority` pode ser usado para definir a prioridade de execução dos Middlewares. Middlewares com maior prioridade (valor numérico mais altos) serão executados antes.

```php
use Daniel\Origins\FilterPriority;

#[FilterPriority(10)]
final class HighPriorityFilter extends Middleware
{
    // Lógica do Middleware
}

#[FilterPriority(1)]
final class LowPriorityFilter extends Middleware
{
    // Lógica do Middleware
}
```

## Programação Orientada a Aspectos (AOP)

A Programação Orientada a Aspectos permite a implementação de preocupações transversais, como logging e segurança, de forma modular e reutilizável.

### Nota

A **Programação Orientada a Aspectos (AOP)** é um recurso avançado do framework, utilizado principalmente para lidar com preocupações transversais, como logging e segurança. **Embora a AOP não modifique diretamente os controladores**, ela pode alterar o comportamento dos métodos, seus argumentos e até o próprio retorno. Como a AOP manipula referências de memória e não clones, ela pode impactar diretamente o estado dos objetos, o que a torna uma área crítica. Devido a esse impacto potencial, é essencial manuseá-la com cuidado, pois alterações inesperadas no fluxo de execução podem afetar outras partes do sistema que dependem dessas referências.


```php
<?php

namespace Daniel\Origins;

use ReflectionMethod;

abstract class Aspect
{
    public function __construct() {}
    abstract public function aspectBefore(object &$controllerEntity, ReflectionMethod &$method, array &$varArgs);
}
?>
```

Para criar um aspecto personalizado, estenda a classe `Aspect` e implemente o método `aspectBefore`:

```php
<?php

namespace App\Aspects;

use Daniel\Origins\Aspect;
use ReflectionMethod;

class LoggingAspect extends Aspect
{
    public function aspectBefore(object &$controllerEntity, ReflectionMethod &$method, array &$varArgs)
    {
        error_log("Executando método: " . $method->getName());
    }
}
?>
```

Isso garante que a lógica personalizada seja executada antes da execução dos métodos dos controllers, proporcionando maior modularidade e manutenibilidade.

---


## Controller Advice

A funcionalidade de Controller Advice permite capturar e tratar exceções de forma centralizada, promovendo maior organização do código.

Para usar, extenda a classe `ControllerAdvice` e sobrescreva o método `onError`. Você pode implementar tratamentos específicos para diferentes tipos de exceções.

### Exemplo

Gerenciamento de autenticação e permissões:

```php
class AuthExceptionHandler extends ControllerAdvice
{
    #[Override]
    public function onError(Throwable $exception): void
    {
        if ($exception instanceof AuthorizationException) {
            header("Location: /login");
            exit;
        }

        if ($exception instanceof AuthorityAuthorizationException) {
            echo "Você não tem permissão para acessar esta página.";
            exit;
        }

        echo "Erro interno do servidor.";
        exit;
    }
}
```

## Log

O sistema de logging integrado permite registrar eventos em arquivos para monitoramento ou auditoria. Use a classe `Log` para registrar informações, avisos ou erros.

### Exemplo de Uso

```php
Log::info("Usuário logado com sucesso", "app.log");
Log::warning("Tentativa de acesso sem permissão", "security.log");
Log::error("Erro inesperado ao processar requisição", "errors.log");
```

## Renderização de Views

O framework permite renderizar páginas dinâmicas com dados. Use o método `renderPage` para passar um modelo de dados a uma view.

```php
// no controlador
renderPage("index.php", ["number" => rand()]);

//no index.php
global $model;
echo $model["number"];
```

## Path Variables

Os endpoints podem conter variáveis de path, como `{id}`. Use o método `getPathVar` para obter os valores dessas variáveis como um array associativo.

### Exemplo

```php
$variaveis = $this->getPathVar();
$id = $variaveis['id'] ?? null;
```

## Exemplo de Código Controller

Aqui está um exemplo de como criar um Controller e mapear um método para um endpoint:

```php
<?php

use Daniel\Origins\Controller;
use Daniel\Origins\Get;
use Daniel\Origins\Request;

#[Controller]
class UserController
{

    #[Inject]
    private Servico $servico;

    #[Get('/users')]
    public function getAllUsers()
    {
        
    }

    #[Get("/")]
    public function index(Request $req){
        $headers = $req->getHeaders();
        $body = $req->getBody();
    }   

    #[Get("/number")]
    public function getNumber(){
        echo $this->servico->getNumber();
    }
}

//true se for apenas 1 instacia e false se for por request
#[Dependency(true)]
class Servico{

    public function getNumber() : int{
        return rand();
    }

}

?>
```

## Exemplo de Código Controller

Aqui está um exemplo de como criar o index:

```php
<?php
    require "./vendor/autoload.php";
    use Daniel\Origins\Origin;

    $app = Origin::initialize();
    $app->run();

?>
```

## Personalizando a Configuração (Opcional)

Se você precisar personalizar a configuração do framework, pode extender a classe:

- **`OnInit.php`**: Este arquivo contem uma classe abstrata que define um método para configurar o framework durante a inicialização.

```php
<?php

   class MinhaConfig extends OnInit{
        //possivel injetar dependecia na inicalização porem apenas classes configuradas para inicar no mesmo momento
    
        #[Override]
        public function ConfigOnInit() : void{
            //sua consfig
        }
    }

?>
```

## Threads com Classe `Thread`

O framework agora oferece suporte à execução de tarefas em threads utilizando a classe `Thread`. Essa funcionalidade é útil para executar tarefas demoradas em segundo plano sem bloquear a execução principal do aplicativo.

### Implementação da Interface `Runnable`

Para utilizar a classe `Thread`, você deve implementar a interface `Runnable` e definir o método `run`, que conterá a lógica da tarefa.

```php
namespace Daniel\Origins;

interface Runnable
{
    public function run();
}
```

### Utilização da Classe `Thread`

A classe `Thread` permite criar e gerenciar threads de forma simples. Veja um exemplo de uso:

```php
namespace Daniel\Origins;

final class ExampleTask implements Runnable
{
    public function run()
    {
        // Lógica da tarefa a ser executada em segundo plano
        echo "Executando tarefa em thread...\n";
    }
}

$runnable = new ExampleTask();
$thread = new Thread($runnable);
$thread->start();

// Aguarda até que a tarefa seja concluída
$thread->waitUntilFinished();

echo "Tarefa concluída!\n";
```

### Métodos Disponíveis na Classe `Thread`

- **`start()`**: Inicia a execução da thread.
- **`isFinished(): bool`**: Verifica se a tarefa foi concluída.
- **`waitUntilFinished(): void`**: Bloqueia a execução até que a tarefa seja concluída.

### Detalhes Técnicos

1. A classe `Thread` utiliza arquivos temporários para gerenciar o estado de execução das threads.
2. O comando de execução é ajustado automaticamente para ambientes Windows e Unix.
3. Ao finalizar a execução, os arquivos temporários são removidos automaticamente.

### Benefícios

- Permite executar tarefas demoradas em segundo plano.
- Evita bloqueios na execução principal do aplicativo.
- Simples de integrar ao código existente.

