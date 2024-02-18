<?php
    require './vendor/autoload.php';

    use Daniel\Origins\ApiController;
use Daniel\Origins\Delete;
use Daniel\Origins\Get;
    use Daniel\Origins\Post;
    use Daniel\Origins\Request;
    
    class Teste extends ApiController{
        public $tes;
        public function __construct(TesteService $tes)
        {
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

    class TesteService{

           
    }

    class Respose{
        private $nome = "teste";
        private $sobrenome = "steste";
        private $email = "jwdck@gmail.com";
        private $idade = 20;
    }

?>