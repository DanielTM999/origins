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

- **Dependency Injection (Injeção de Dependência)**: O framework utiliza injeção de dependência para gerenciar e injetar automaticamente as dependências necessárias nas classes.

- **Atributos de Roteamento**: Os atributos `Get`, `Post`, `Delete` e `Put` são utilizados para mapear métodos de controller para endpoints da API.

- **Auto Mapeador de classes e auto carregamento**: O framework utiliza um mapeador propria para fazer o carregamento das classes e dependecias sem o uso de composer ou outro mapeador;

## Uso Básico

Para criar um aplicativo web usando o framework Origins, siga estas etapas:

1. **Defina seus Controllers**: Crie classes de Controller e marque-as com o atributo `Controller`.

2. **Defina os Endpoints**: Utilize os atributos `Get`, `Post`, `Delete` e `Put` para mapear os métodos dos Controllers para os endpoints da API.

3. **Inicie o Framework**: Crie uma instância do `Origin` e chame o método `run()` para iniciar o roteamento e despachar as solicitações.

## Exemplo de Código Controller

Aqui está um exemplo de como criar um Controller e mapear um método para um endpoint:

```php
<?php

namespace App\Controllers;

use Daniel\Origins\Controller;
use Daniel\Origins\Get;
use Daniel\Origins\Request;

#[Controller]
class UserController extends Controller
{

    #[Inject(Servico::class)]
    private $servico;

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
