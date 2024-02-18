<?php
    namespace Daniel\Origins;

    use Daniel\Origins\JsonSerializable;
    use ReflectionClass;
    use Daniel\Origins\Router;
    use Daniel\Origins\HttpMethod;
    use Exception;
    use ReflectionMethod;
    use Daniel\Origins\Request;

    class Origins{
        private static Origins $instance;
        private static $di_dinamic = [];
        private static $routes = [];

        public static function initialize() : Origins {
            if(!isset(self::$instance)){
                self::$instance = new self();
            }
            return self::$instance;
        }

        private function __construct() {
           
        }

        public function AddDependency(string $dependecy) {
            $reflect = new ReflectionClass($dependecy);
            
            $contructFunction = function() use ($reflect){
                $constructor = $reflect->getConstructor();
                if ($constructor !== null) 
                {
                    $parameters = $constructor->getParameters();
                    $resolvedArgs = [];
                    foreach ($parameters as $param){
                        $paramClass = $param->getType();
                         
                        if ($paramClass !== null && !$paramClass->isBuiltin()) {
                            $paramClassName = $paramClass->getName();
                            $object = $this->DiConteins($paramClassName);
                            $resolvedArgs[] = $object();
                        } else {
                            echo "Tipo primitivo ou não definidoc para conreutor de classe <br>";
                        }
                    }
                    return $reflect->newInstanceArgs($resolvedArgs);

                }else{
                    
                    return $reflect->newInstance();
                }
            };
            
            $data = [
                $reflect->getName() => $contructFunction
            ];
            self::$di_dinamic[] = $data;
        }

        public function AddDependencyCustom($dependency = null) {
            $object = null;
            if(!is_callable($dependency)){
                throw new Exception("passe uma função de inicialização");
            }else if(($object = $dependency()) === null){
                throw new Exception("passe uma função que retorne uma intancia");
            }

            $reflect = new ReflectionClass($object);
            $data = [
                $reflect->getName() => $dependency
            ];
            self::$di_dinamic[] = $data;
        }

        public function EnableEndPoint(){
            $classes = get_declared_classes();

            foreach ($classes as $class) {
                $reflect = new ReflectionClass($class);
                $parent = $reflect->getParentClass();
                if(is_object($parent) && ($parent->getName() === "Daniel\Origins\ApiController")){
                    $this->mappingControllerClass(new ReflectionClass($class));
                }
            }
        }

        public function Run(){
            $requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            $requestMethod = $_SERVER['REQUEST_METHOD'];
            $headers = getallheaders();

            $body = file_get_contents('php://input');
            $jsonData = json_decode($body, true);
            
            $padrao = "/\{([^}]+)\}/";
            $varId = null;
            $indexOf = 0;
            foreach(self::$routes as $route){
                	
                if(preg_match($padrao, $route->path, $correspondencias)){
                    $indexOf = strpos($route->path, "{");
                    $varId = substr($requestPath, $indexOf, strlen($requestPath));
                    $route->path = substr($route->path, 0, $indexOf);
                    $requestPath = substr($requestPath, 0, $indexOf);
                }

                if($route->method === $requestMethod && $route->path === $requestPath){
                    if ($jsonData !== null){
                        $req = new Request($headers, $jsonData, $varId);
                    }else{
        
                        if($requestMethod === "GET"){
                            $req = new Request($headers, $_GET, $varId);
                        }else{
                            $req = new Request($headers, $_POST, $varId);
                        }
        
                    } 
                    $instance = $this->getInstanceByRoute($route->class);
                    $method = $route->methodClass;
                    $this->ExecuteMethod($method, $instance, $req);
                    return;
                }
            }

            http_response_code(404);
            echo $this->renderError404Page();
        }

        public function ShowDependecys(){
            foreach(self::$di_dinamic as $key => $dependecy){
                foreach($dependecy as $keyintern => $dependecyExt){
                    var_dump($keyintern);
                    var_dump($dependecyExt);
                    echo "<br>";
                    echo "<br>";
                }
            }
        }

        private function DiConteins(string $class){
            $data = null;
            foreach(self::$di_dinamic as $dependecy){
                if (isset($dependecy[$class])) {
                    $data = $dependecy[$class];
                    break; 
                }
            }

            return $data;
        }

        private function mappingControllerClass(ReflectionClass $reflect){
            $methods = $reflect->getMethods();
            foreach($methods as $method){
                $attributes = $method->getAttributes();
                foreach ($attributes as $attribute) {
                    $atrubute_name = $attribute->getName();
                    switch($atrubute_name){
                        case "Daniel\Origins\Get":
                            $args = $attribute->getArguments();
                            $this->addRouteGet($args[0], $reflect, $method);
                            break;
                        case "Daniel\Origins\Post":
                                $args = $attribute->getArguments();
                                $this->addRoutePost($args[0], $reflect, $method);
                                break;
                        case "Daniel\Origins\Delete":
                                $args = $attribute->getArguments();
                                $this->addRouteDelete($args[0], $reflect, $method);
                                break;
                        case "Daniel\Origins\Put":
                                $args = $attribute->getArguments();
                                $this->addRoutePut($args[0], $reflect, $method);
                                break;
                        
                    }
                }
            }
        }

        private function addRouteGet(string $Path, ReflectionClass $class, ReflectionMethod $method){
            $route = new Router($Path, HttpMethod::GET, $class, $method);
            self::$routes[] = $route;
        }

        private function addRoutePost(string $Path, ReflectionClass $class, ReflectionMethod $method){
            $route = new Router($Path, HttpMethod::POST, $class, $method);
            self::$routes[] = $route;
        }

        private function addRouteDelete(string $Path, ReflectionClass $class, ReflectionMethod $method){
            $route = new Router($Path, HttpMethod::DELETE, $class, $method);
            self::$routes[] = $route;
        }

        private function addRoutePut(string $Path, ReflectionClass $class, ReflectionMethod $method){
            $route = new Router($Path, HttpMethod::PUT, $class, $method);
            self::$routes[] = $route;
        }

        private function getInstanceByRoute(ReflectionClass $reflect){
            $constructor = $reflect->getConstructor();
            if ($constructor !== null) {
                $parameters = $constructor->getParameters();
                $resolvedArgs = [];
                foreach ($parameters as $param){
                    $paramClass = $param->getType();
                     
                    if ($paramClass !== null && !$paramClass->isBuiltin()) {
                        $paramClassName = $paramClass->getName();
                        $object = $this->DiConteins($paramClassName);
                        $resolvedArgs[] = $object();
                        return $reflect->newInstanceArgs($resolvedArgs);
                    } else {
                        echo "Tipo primitivo ou não definidoc para conreutor de classe <br>";
                    }
                }
            }else{
                return $reflect->newInstance();
            }
        }

        private function ExecuteMethod(ReflectionMethod $method, $entity, Request $req){
            try{
                $parameters = $method->getParameters();
                if($parameters !== null){
                    $args = [];
                    foreach($parameters as $param){
                        $paramType = $param->getType();
                        switch($paramType){
                            case "Daniel\Origins\Request":
                                $args[] = $req;
                                break;
                            default:
                                $args[] = null;
                                break;
                        }
                    }
                    $method->invokeArgs($entity, $args);
                }else{
                    $method->invoke($entity);
                }
            }catch(Exception $e){

            }

        }

        private function renderError404Page()
        {
            $html = '
                <!DOCTYPE html>
                <html lang="pt-br">
                <head>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <title>Página não encontrada</title>
                    <style>
                        body {
                            background-color: #f8f9fa;
                            font-family: Arial, sans-serif;
                            display: flex;
                            justify-content: center;
                            align-items: center;
                            height: 100vh;
                            margin: 0;
                        }

                        .error-container {
                            text-align: center;
                        }

                        .error-image {
                            max-width: 100%;
                            height: auto;
                        }

                        h1 {
                            color: #dc3545;
                            font-size: 4em;
                        }

                        p {
                            font-size: 1.2em;
                            color: #555;
                        }

                        a {
                            color: #007bff;
                            text-decoration: none;
                            font-weight: bold;
                        }

                        a:hover {
                            text-decoration: underline;
                        }
                    </style>
                </head>
                <body>
                    <div class="error-container">
                        <img class="error-image" src="/src/4835105_404_icon.png" alt="Erro 404 - Página não encontrada">
                        <h1>Oops! Página não encontrada</h1>
                        <p>A página que você está procurando não foi encontrada. <br>Verifique o URL ou <a href="/">volte para a página inicial</a>.</p>
                    </div>
                </body>
                </html>';

            return $html;
        }

    }

    
?>