<?php
    namespace Daniel\Origins;

    use Daniel\Origins\Exceptions\CompositeException;
    use Exception;
    use Override;
    use ReflectionClass;
    use ReflectionMethod;
    use Throwable;
    use Daniel\Origins\HttpMethod;
    use Daniel\Origins\Annotations\ContentType;
    use Daniel\Origins\Annotations\Controller;
    use Daniel\Origins\Annotations\Get;
    use Daniel\Origins\Annotations\Post;
    use Daniel\Origins\Annotations\Put;
    use Daniel\Origins\Annotations\Delete;
    use Daniel\Origins\Annotations\Patch;

    class ServerDispacher extends Dispacher{
        public static $routes = [];
        private static $middlewares = [];
        private static ?ReflectionClass $controllerErrorReflect;
        private static ?ControllerAdvice $controllerError;

        
        #[Override]
        public function map(): void{
            if(isset($_SESSION["origins.loaders"])){
                $loaders = $_SESSION["origins.loaders"];    
                $controllers = $loaders["controllers"] ?? [];
                $middlewares = $loaders["middlewares"] ?? [];
                $routes = $loaders["routes"] ?? [];
                $controllerAdvice = $loaders["controllerAdvice"] ?? null;
                self::$controllerError = null;
                if(isset($controllerAdvice)){
                    $reflect = new ReflectionClass($controllerAdvice);
                    self::$controllerErrorReflect = $reflect;
                }

                if(empty($routes)){
                    foreach($controllers as $controller){
                        $reflect = new ReflectionClass($controller);
                        $this->mappingControllerClass($reflect);
                    }
                    if(isset($_ENV["enviroment"]) && $_ENV["enviroment"] === "production"){
                        $this->addRoutesToCash();
                    }
                }else{
                    $reflectMap = [];
                    foreach($routes as $route){
                        $className = $route["class"];
                        if (isset($reflectMap[$className])) {
                            $reflectController = $reflectMap[$className];
                        } else {
                            $reflectController = new ReflectionClass($className);
                            $reflectMap[$className] = $reflectController;
                        }   
                        $reflectMethod = $reflectController->getMethod($route["action"]["name"]);
                        self::$routes[] = new Router($route["path"], $route["httpMethod"], $reflectController, $reflectMethod);
                    }
                    unset($reflectMap);
                }

                foreach($middlewares as $middleware){
                    $reflect = new ReflectionClass($middleware);
                    self::$middlewares[] = $reflect;
                }

            }else{
                $this->mapIfNotloaded();
            }
            
            usort(self::$middlewares, function($a, $b){
                $attributesA = $a->getAttributes(FilterPriority::class);
                $attributesB = $b->getAttributes(FilterPriority::class);

                $priorityAArgs0 = isset($attributesA[0]) ? $attributesA[0]->getArguments() : [0];
                $priorityBArgs0 = isset($attributesB[0]) ? $attributesB[0]->getArguments() : [0];
                $priorityA = isset($priorityAArgs0[0]) ? $priorityAArgs0[0] : 0;
                $priorityB = isset($priorityBArgs0[0]) ? $priorityBArgs0[0] : 0;

                return $priorityB <=> $priorityA;
            });
            
        }

        #[Override]
        public function addExternalRoute(Router $route): void{
            if(isset($route)){
                self::$routes[] = $route;
            }
        }

        #[Override]
        public function addExternalRouteAtrubutes(string $path, string $httpMethod, ReflectionClass $class, ReflectionMethod $method): void{
            if(isset($path) && isset($httpMethod) && isset($class) && isset($method)){
                if($httpMethod === HttpMethod::GET || $httpMethod === HttpMethod::POST || $httpMethod === HttpMethod::DELETE || $httpMethod === HttpMethod::PUT){
                    $route = new Router($path, $httpMethod, $class, $method);
                    self::$routes[] = $route;
                }
            }
        }

        #[Override]
        public function dispach(DependencyManager $Dmanager): void{
            $hostClient = $_SERVER['REMOTE_ADDR'];
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
                $pattern = preg_quote($route->path, '#');
                $pattern = preg_replace('/\\\{([^}]+)\\\}/', '([^/]+)', $pattern);
                $pattern = '#^' . $pattern . '$#';

                if ($route->method === $requestMethod && preg_match($pattern, $requestPath, $matches)){
                    array_shift($matches);

                    $pathVariables = [];
                    if (preg_match_all('/\{([^\/]+)\}/', $route->path, $varNames)) {
                        $pathVariables = array_combine($varNames[1], $matches);
                    }

                    $req = ($jsonData !== null)
                        ? new Request($headers, $jsonData, $pathVariables, $requestPath, $hostClient, $route)
                        : new Request($headers, ($requestMethod === "GET" ? $_GET : $_POST), $pathVariables, $requestPath, $hostClient, $route);


                    $instance = $Dmanager->tryCreate($route->class);
                    $method = new ReflectionMethod($instance, $route->methodClass->getName());

                    try {
                        $methodArgs = $this->getMainMethodExecuteArgs($method, $route->methodClass, $req);
                        foreach(self::$middlewares as $md){
                            $instanceMiddleware = $Dmanager->tryCreate($md);
                            $this->ExecuteMiddleware($instanceMiddleware, $req);
                        }

                        $this->ExecuteMethod($method, $route->methodClass, $instance, $methodArgs);
                    } catch (\Throwable $th) {
                        $this->executeControllerAdviceException($route->class, $th, $Dmanager);
                    }
                    return;
                }
            }

            http_response_code(404);
            echo $this->renderError404Page();
        }

        #[Override]
        public function ShowEndPoints($writeAsJson = false): void
        {
            $cont = count(self::$routes);
            if($writeAsJson){
               
                $endpoints = [];
                foreach (self::$routes as $key => $value) {
                    $endpoints[] = [
                        'rota' => $value->path,
                        'metodo' => $value->method,
                        'classe' => $value->class->getName(),
                        'action' => $value->methodClass,
                    ];
                }

                $localPath = $_ENV["base.dir"] . "/endpoitsDoc.json";

                file_put_contents($localPath, json_encode($endpoints, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                
                return;
            }
           
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
            $attribute = $reflect->getAttributes(Controller::class);
            $args = $attribute[0]->getArguments();
            $location = "";
            if(isset($args[0])){
                $location = $args[0];
                if (!str_starts_with($location, '/')) {
                    $location = '/' . $location;
                }
            }
            foreach ($methods as $method){
               $this->findMethodHttp($method, $reflect, $location);
            }

        }

        private function findMethodHttp(ReflectionMethod $method, $reflect, $pathMapping){
            $attributes = $method->getAttributes();
            $namemethod = $method->getName();
            foreach ($attributes as $attribute){
                $atrubute_name = $attribute->getName();
                switch ($atrubute_name) {
                    case Get::class:
                        $args = $attribute->getArguments();
                        if(strpos($args[0], "[action]") !== false){
                            $args[0] = str_replace("[action]", $namemethod, $args[0]);
                        }
                        $path = $pathMapping . $args[0];
                        $this->addRoute($path, $reflect, $method, HttpMethod::GET);
                        break;
                    case Post::class:
                        $args = $attribute->getArguments();
                        if(strpos($args[0], "[action]") !== false){
                            $args[0] = str_replace("[action]", $namemethod, $args[0]);
                        }
                        $path = $pathMapping . $args[0];
                        $this->addRoute($path, $reflect, $method, HttpMethod::POST);
                        break;
                    case Delete::class:
                        $args = $attribute->getArguments();
                        if(strpos($args[0], "[action]") !== false){
                            $args[0] = str_replace("[action]", $namemethod, $args[0]);
                        }
                        $path = $pathMapping . $args[0];
                        $this->addRoute($path, $reflect, $method, HttpMethod::DELETE);
                        break;
                    case Put::class:
                        $args = $attribute->getArguments();
                        if(strpos($args[0], "[action]") !== false){
                            $args[0] = str_replace("[action]", $namemethod, $args[0]);
                        }
                        $path = $pathMapping . $args[0];
                        $this->addRoute($path, $reflect, $method, HttpMethod::PUT);
                        break;
                    case Patch::class:
                        $args = $attribute->getArguments();
                        if(strpos($args[0], "[action]") !== false){
                            $args[0] = str_replace("[action]", $namemethod, $args[0]);
                        }
                        $path = $pathMapping . $args[0];
                        $this->addRoute($path, $reflect, $method, HttpMethod::PATCH);
                        break;
                }
            }
        }

        private function addRoute(string $Path, ReflectionClass $class, ReflectionMethod $method, string $httpMethod){
            $route = new Router($Path, $httpMethod, $class, $method);
            self::$routes[] = $route;
        }

        private function renderError404Page()
        {   
            try {
                if(isset($_ENV["notfoundPage"])){
                    $htmlFilePath = $_ENV["notfoundPage"];            
                    if (file_exists($htmlFilePath)){
                        return file_get_contents($htmlFilePath);
                    }
                    return $this->getDefaltError404Page();
                }else{
                    return $this->getDefaltError404Page();
                }
            } catch (\Throwable $th) {
                return $this->getDefaltError404Page();
            }

        }

        private function getDefaltError404Page(){
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
                            <h1>Oops! Página não encontrada</h1>
                            <p>A página que você está procurando não foi encontrada. <br>Verifique o URL ou <a href="/">volte para a página inicial</a>.</p>
                        </div>
                    </body>
                    </html>';

            return $html;
        }

        private function ExecuteMethod(ReflectionMethod $method, ReflectionMethod $realMethod, $entity, array &$args)
        {   
            try {
                $parameters = $method->getParameters();
                
                if ($parameters !== null) {
                    $result = $method->invokeArgs($entity, $args);
                    $this->setContentType($realMethod);
                    $this->echoResult($result);
                } else {
                    $result = $method->invoke($entity);
                    $this->setContentType($realMethod);
                    $this->echoResult($result);
                }
            } catch (Exception $e) {
                throw $e;
            }
        }

        private function setContentType(ReflectionMethod $realMethod){
            if(AnnotationsUtils::isAnnotationPresent($realMethod, ContentType::class)){
                $headers = headers_list();
                $contentTypeSet = false;

                foreach ($headers as $header) {
                    if (stripos($header, 'Content-Type:') === 0) {
                        $contentTypeSet = true;
                        break;
                    }
                }
                if($contentTypeSet) return;

                $atrubuteArgs = AnnotationsUtils::getAnnotationArgs($realMethod, ContentType::class);

                $contentType = $atrubuteArgs[0] ?? ContentType::HTML;
                header('Content-Type: ' . $contentType);
            }
        }

        private function getMainMethodExecuteArgs(ReflectionMethod $method, ReflectionMethod $realMethod, Request $req): array{
            $args = [];

            try{
                $parameters = $method->getParameters();
                if ($parameters !== null){
                    foreach ($parameters as $param){
                        $paramType = $param->getType();
                        if(isset($paramType)){
                            $paramNameTypeName = $paramType->getName();
                            switch ($paramNameTypeName) {
                                case Request::class:
                                    $args[] = $req;
                                    break;
                                case Response::class:
                                    $args[] = new Response();
                                    break;
                                case Module::class:
                                    $args[] = ModuleManager::getCurrentModule($realMethod);
                                    break;
                                default:
                                    $args[] = null;
                                    break;
                            }
                        }else{
                            $args[] = null; 
                        }
                    }
                }
            }catch(Throwable $e){
                throw $e;
            }

            return $args;
        }

        private function ExecuteMiddleware(Middleware $entity, Request $req){
            $entity->onPerrequest($req);
        }

        private function addToControllerAdvice(ReflectionClass $reflection){
            $atribute = $reflection->getAttributes(ControllerAdvice::class);
            if(isset($atribute) && !empty($atribute)){
                $_SESSION["controllerAdvice"][] = $reflection;
                $_SESSION["selectedClass"][] =  $reflection->getName();
            }
        }

        private function executeControllerAdviceException($entityName, Throwable $throwable, DependencyManager $Dmanager){
            if(isset(self::$controllerErrorReflect)){
                self::$controllerError = $Dmanager->tryCreate(self::$controllerErrorReflect);
                self::$controllerError->onError($throwable);
            }else{
                http_response_code(500);
                header('Content-Type: application/json; charset=utf-8');
                
                if($throwable instanceof CompositeException){
                    $messageList = $throwable->getErrorsAsString();
                }else{
                    $messageList = [
                        $throwable->getMessage()
                    ];
                }
                $response = [
                    'status' => 'error',
                    'httpCode' => 500,
                    'timestamp' => date('c'),
                    'controller' => $entityName,
                    'exception' => get_class($throwable),
                    'messages' => $messageList,
                    'code' => $throwable->getCode(),
                    'stackTrace' => explode("\n", $throwable->getTraceAsString())
                ];
                echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            }
        }

        private function echoResult($result){
            if($result !== null){
                if (is_scalar($result)){
                    echo $result;
                }else if(is_array($result)) {
                    echo json_encode($result);
                }else if (is_object($result)) {
                    if (method_exists($result, '__toString')){
                        echo $result->__toString();
                    }else{
                        echo serialize($result);
                    }
                }
            }
        }

        private function mapIfNotloaded(){
            $exec = (empty($_ENV["enviroment"]) || $_ENV["enviroment"] == 'dev') ? false : true;
            if(isset($_SESSION["selectedClass"]) && $exec){
                foreach ($_SESSION["selectedClass"] as $class){
                    $reflect = new ReflectionClass($class);
                    $atribute = $reflect->getAttributes(Controller::class);
    
                    if (isset($atribute) && !empty($atribute)){
                        $this->mappingControllerClass($reflect);
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
    
                    if(!isset($_SESSION["controllerAdvice"]) || empty($_SESSION["controllerAdvice"])){
                        $this->addToControllerAdvice($reflect);
                    }
                }
            }else{
                unset($_SESSION["selectedClass"]);
                $classes = get_declared_classes();
                foreach ($classes as $class){
                    $reflect = new ReflectionClass($class);
                    $atribute = $reflect->getAttributes(Controller::class);
    
                    if (isset($atribute) && !empty($atribute)){
                        $this->mappingControllerClass($reflect);
                        $_SESSION["selectedClass"][] =  $class;
                    }
    
                    $parentClass = $reflect->getParentClass();
                    if ($parentClass !== false) {
                        $parentClassName = $parentClass->getName();
                        if($parentClassName === Middleware::class){
                            $_SESSION["selectedClass"][] =  $class;
                            self::$middlewares[] = $reflect;
                        }else if($parentClassName === ControllerAdvice::class){
                            $_SESSION["selectedClass"][] =  $class;
                            self::$controllerErrorReflect = $reflect;
                        }
                    }
    
                    if(!isset($_SESSION["controllerAdvice"]) || empty($_SESSION["controllerAdvice"])){
                        $this->addToControllerAdvice($reflect);
                    }
                }   
            }
        }

        private function addRoutesToCash(){
            $settings = $this->getCache();
            $endpoints = [];
            foreach (self::$routes as $key => $value) {
                $endpoints[] = [
                    'path' => $value->path,
                    'httpMethod' => $value->method,
                    'class' => $value->class->getName(),
                    'action' => $value->methodClass,
                ];
            }
            $settings["configurations"]["routes"] = $endpoints;
            $jsonData = json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            file_put_contents(ServerAutoload::$metaDadosPath, $jsonData);
        }

        private function getCache(){
            $filePath = ServerAutoload::$metaDadosPath;
            if (!file_exists($filePath)) {
                return null;
            }
            $jsonData = file_get_contents($filePath);
            if ($jsonData === false) {
                return null;
            }
            $data = json_decode($jsonData, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return null;
            }
            return $data;
        }

    }

 
?>