<?php
    namespace Daniel\Origins;

    use Exception;
    use Override;
    use ReflectionClass;
    use ReflectionMethod;
    use ReflectionProperty;

    class ServerDispacher extends Dispacher{
        public static $routes = [];
        private static $middlewares = [];
        private static ReflectionClass $controllerErrorReflect;
        private static ControllerAdvice $controllerError;

        #[Override]
        public function map(): void{
            $classes = get_declared_classes();
            foreach ($classes as $class){
                $reflect = new ReflectionClass($class);
                $atribute = $reflect->getAttributes(Controller::class);

                if (isset($atribute) && !empty($atribute)){
                    $this->mappingControllerClass(new ReflectionClass($class));
                }

                $parentClass = $reflect->getParentClass();
                if ($parentClass !== false) {
                    $parentClassName = $parentClass->getName();
                    if($parentClassName === Middleware::class){
                        self::$middlewares[] = $reflect;
                    }else if($parentClassName === ControllerAdvice::class){
                        self::$controllerErrorReflect = $reflect;
                    }
                }
            }

        }

        #[Override]
        public function dispach(DependencyManager $Dmanager): void{
            $requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            $requestMethod = $_SERVER['REQUEST_METHOD'];
            $headers = getallheaders();
            $body = file_get_contents('php://input');
            try {
                $jsonData = json_decode($body, true);
            } catch (\Throwable $th) {
                $jsonData = null;
            }

            foreach (self::$routes as $route){
                if ($route->method === $requestMethod && $route->path === $requestPath){

                    if ($jsonData !== null) {
                        $req = new Request($headers, $jsonData, "");
                    } else {
                        if ($requestMethod === "GET") {
                            $req = new Request($headers, $_GET, "");
                        } else {
                            $req = new Request($headers, $_POST, "");
                        }
                    }

                    $instance = $this->getInstanceBy($route->class, $Dmanager);
                    $method = $route->methodClass;

                    try {
                        foreach(self::$middlewares as $md){
                            $instanceMiddleware = $this->getInstanceBy($md, $Dmanager);
                            $this->ExecuteMiddleware($instanceMiddleware, $req);
                        }
                        $this->ExecuteMethod($method, $instance, $req);
                    } catch (\Throwable $th) {
                        try {
                            if(isset(self::$controllerErrorReflect)){
                                self::$controllerError = $this->getInstanceBy(self::$controllerErrorReflect, $Dmanager);
                                self::$controllerError->onError($th);
                            }else{
                                throw $th;  
                            }
                        } catch (\Throwable $th1) {
                            throw $th1;
                        }
                    }
                    return;
                }
            }

            http_response_code(404);
            echo $this->renderError404Page();
        }

        #[Override]
        public function ShowEndPoints(): void
        {
            $cont = count(self::$routes);
            echo "Endpoints encontrados: $cont <br>";
            foreach (self::$routes as $key => $value) {
                $rota = $value->path;
                $method = $value->method;
                $act = $value->methodClass;
                $class = $value->class->getName();
                echo "<br>";
                echo "rota: $rota <br>";
                echo "metodo: $method <br>";
                echo "Classe: $class <br>";
                echo "action: $act <br>";
                echo "<hr>";
            }
        }

        private function mappingControllerClass(ReflectionClass $reflect){
            $methods = $reflect->getMethods();
            foreach ($methods as $method){
               $this->findMethodHttp($method, $reflect, "");
            }

        }

        private function findMethodHttp($method, $reflect, $pathMapping){
            $attributes = $method->getAttributes();
            foreach ($attributes as $attribute){
                $atrubute_name = $attribute->getName();
                switch ($atrubute_name) {
                    case Get::class:
                        $args = $attribute->getArguments();
                        $path = $pathMapping . $args[0];
                        $this->addRouteGet($path, $reflect, $method);
                        break;
                    case Post::class:
                        $args = $attribute->getArguments();
                        $path = $pathMapping . $args[0];
                        $this->addRoutePost($path, $reflect, $method);
                        break;
                    case Delete::class:
                        $args = $attribute->getArguments();
                        $path = $pathMapping . $args[0];
                        $this->addRouteDelete($path, $reflect, $method);
                        break;
                    case Put::class:
                        $args = $attribute->getArguments();
                        $path = $pathMapping . $args[0];
                        $this->addRoutePut($path, $reflect, $method);
                        break;
                }
            }
        }

        private function addRouteGet(string $Path, ReflectionClass $class, ReflectionMethod $method)
        {
            $route = new Router($Path, HttpMethod::GET, $class, $method);
            self::$routes[] = $route;
        }

        private function addRoutePost(string $Path, ReflectionClass $class, ReflectionMethod $method)
        {
            $route = new Router($Path, HttpMethod::POST, $class, $method);
            self::$routes[] = $route;
        }
    
        private function addRouteDelete(string $Path, ReflectionClass $class, ReflectionMethod $method)
        {
            $route = new Router($Path, HttpMethod::DELETE, $class, $method);
            self::$routes[] = $route;
        }
    
        private function addRoutePut(string $Path, ReflectionClass $class, ReflectionMethod $method)
        {
            $route = new Router($Path, HttpMethod::PUT, $class, $method);
            self::$routes[] = $route;
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

        private function getInstanceBy(ReflectionClass $reflect, DependencyManager $Dmanager){
            $constructor = $reflect->getConstructor();
            $vars = $reflect->getProperties();

            if ($constructor !== null){
                $parameters = $constructor->getParameters();
                if(empty($parameters)){
                    return $this->injectNoContructors($reflect, $vars, $Dmanager);
                }
            }else{
                return $this->injectNoContructors($reflect, $vars, $Dmanager);
            }
        }

        private function injectNoContructors(ReflectionClass $reflect, $vars, DependencyManager $Dmanager)
        {
            $instance = $reflect->newInstance();
            if (count($vars) > 0) {
                foreach ($vars as $prop) {
                    if ($this->isAnnotetionPresent($prop, Inject::class)) {
                        $propClass = $prop->getType();
                        $args = $this->getAnnotetion($prop, Inject::class);
                        if (isset($propClass)) {
                            $object = $Dmanager->get($propClass);
                            $prop->setAccessible(true);
                            $prop->setValue($instance, $object);
                        }else if(!empty($args)){
                            $object = $Dmanager->get($args[0]);
                            $prop->setAccessible(true);
                            $prop->setValue($instance, $object);
                        }else {
                            echo "Não posso injetar algo na variavel [ {$prop->getName()} ], essa variavel tem que possuir um tipo";
                            die();
                        }
                    }
                }
            }
            return $instance;
        }

        private function isAnnotetionPresent(ReflectionProperty $prop, string $atribute): bool
        {
            $attributes  = $prop->getAttributes();
            foreach ($attributes as $attribute) {
                if ($attribute->getName() === $atribute) {
                    return true;
                }
            }
            return false;
        }

        private function getAnnotetion(ReflectionProperty $prop, string $atribute)
        {
            $attributes  = $prop->getAttributes();
            foreach ($attributes as $attribute) {
                if ($attribute->getName() === $atribute) {
                    return $arguments = $attribute->getArguments();
                }
            }
            return null;
        }

        private function ExecuteMethod(ReflectionMethod $method, $entity, Request $req)
        {
            
            try {
                $parameters = $method->getParameters();
                if ($parameters !== null) {
                    $args = [];
                    foreach ($parameters as $param) {
                        $paramType = $param->getType()->getName();
                        
                        switch ($paramType) {
                            case Request::class:
                                $args[] = $req;
                                break;
                            case Response::class:
                                $args[] = new Response();
                                break;
                            default:
                                $args[] = null;
                                break;
                        }
                    }
                    try {
                        $method->invokeArgs($entity, $args);
                    } catch (\Throwable $th) {
                        $reflect = new ReflectionClass($entity);
                        $name = $reflect->getName();
                        $error = $th->getMessage();
                        echo "<b>Error:</b> [$name] --> $error";
                    }
                } else {
                    $method->invoke($entity);
                }
            } catch (Exception $e) {
                var_dump($e->getMessage());
            }
        }

        private function ExecuteMiddleware(Middleware $entity, Request $req){
            $entity->onPerrequest($req);
        }
    }

    
?>