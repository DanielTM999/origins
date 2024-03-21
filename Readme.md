# Documentação do Framework Origins

## Descrição
Origins é um framework PHP leve para construção de APIs RESTful. Ele oferece uma abordagem simples e elegante para definir endpoints, gerenciar dependências e lidar com requisições HTTP.

## Exemplo de Uso

```php
<?php
    require "./vendor/autoload.php";
    include "./RotaController.php";
    include "./Teste.php";
    use Daniel\Origins\Origins;

    try{
        $app = Origins::initialize();
        $app->AddSingleton(PessoaRepository::class);
        $app->AddMiddleware(new Filter());
        $app->EnableEndPoint();
        $app->Run();
    }catch(Exception $ex){
        echo $ex->getMessage(); 
    }
?>
```

## Estrutura do Framework

### O framework consiste em três componentes principais:
`ApiController` :  Classe base para controladores de API.<br>
`Router`: Classe que mapeia os endpoints definidos nos controladores.<br>
`Origins`: Classe principal que inicializa o framework, gerencia dependências e executa a lógica de roteamento.<br>

## Exemplo de Definição de Controlador de API

```php
<?php
    // a Request $request será injetada no metodo se passar a classe  
    use Daniel\Origins\ApiController;
    use Daniel\Origins\Get;
    use Daniel\Origins\Post;
    use Daniel\Origins\Delete;
    use Daniel\Origins\Request;

    class Teste extends ApiController{
        public $tes;
        
        public function __construct(TesteService $tes){
            $this->tes = $tes;
        }

        #[Get("/")]
        public function userAuth(Request $request){
            $this->sendObject(new Respose());
        }  
        
        #[Get("/main")]
        public function rota(Request $request){
            $this->Render("/view/tela.html");
        } 
        
        #[Delete("/")]
        public function exclur(){
            $this->send("deletado");
        }
        
        #[Post("/")]
        public function tesfun(){
            $this->send("api POST");
        }
    }
?>

``` 


## Exemplo de Definição de seviço de API

```php
<?php
    // implemente no injetor de dependecia com $app->AddDependency(TesteService::class);
    class TesteService{
        // Implementação do serviço
    }

?>
```

## Exemplo de Definição de Middleware
```php
<?php
    // implemente a interface de dependecia MiddlewareFilter e chame a instancia $app->AddMiddleware(new Filter());
    class Filter implements MiddlewareFilter{
        public function invokeHandle(): void{
            echo "middleware executado<br>";
        }
    }
?>
```

## Conclusão

O Framework Origins oferece uma solução simples e eficaz para o desenvolvimento de APIs RESTful em PHP, com recursos para gerenciamento de dependências, roteamento de requisições e manipulação de endpoints de forma intuitiva e flexível.