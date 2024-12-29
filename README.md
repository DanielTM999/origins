# Documentação do Framework PHP Origins

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
    public function intex(Request $req){
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