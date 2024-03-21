<?php

namespace DanielTm\Origins;

use Daniel\Origins\JsonSerializable;
use ReflectionClass;
use DanielTm\Origins\Router;
use DanielTm\Origins\HttpMethod;
use DanielTm\Origins\MiddlewareFilter as OriginsMiddlewareFilter;
use Exception;
use ReflectionMethod;
use DanielTm\Origins\Request;
use MiddlewareFilter;
use ReflectionProperty;

class Origins
{
    private static Origins $instance;
    private static $di_dinamic = [];
    public static $pathClass = [];
    private static $filterMiddlweare = [];
    private $routes = [];

    public static function initialize(): Origins
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        $reflect = new ReflectionClass($this);
        $data = [
            $reflect->getName() => $this
        ];
        self::$di_dinamic[] = $data;
    }

    public function AddDependency(string $dependecy)
    {
        $reflect = new ReflectionClass($dependecy);

        $contructFunction = function () use ($reflect) {
            $constructor = $reflect->getConstructor();
            if ($constructor !== null) {
                $parameters = $constructor->getParameters();
                $resolvedArgs = [];
                foreach ($parameters as $param) {
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
            } else {

                return $reflect->newInstance();
            }
        };

        $data = [
            $reflect->getName() => $contructFunction
        ];
        self::$di_dinamic[] = $data;
    }

    public function AddDependencyCustom($dependency = null)
    {
        $object = null;
        if (!is_callable($dependency)) {
            throw new Exception("passe uma função de inicialização");
        } else if (($object = $dependency()) === null) {
            throw new Exception("passe uma função que retorne uma intancia");
        }

        $reflect = new ReflectionClass($object);
        $data = [
            $reflect->getName() => $dependency
        ];
        self::$di_dinamic[] = $data;
    }

    public function AddMiddleware(string $filter)
    {
        $reflect = new ReflectionClass($filter);
        $interfaces = $reflect->getInterfaceNames();
        if(empty($interfaces)){
            throw new Exception("implemente a interfaçe [ ".OriginsMiddlewareFilter::class." ] na classe [ {$filter} ]");
        }
        $interfaceMiidlweare = $interfaces[0];
        if($interfaceMiidlweare != OriginsMiddlewareFilter::class){
            throw new Exception("implemente a interfaçe [ ".OriginsMiddlewareFilter::class." ] na classe [ {$filter} ]");
        }
        $object = $this->getInstanceBy($reflect);
        self::$filterMiddlweare[] = $object;
    }

    public function AddSingleton(string $dependecy)
    {
        $reflect = new ReflectionClass($dependecy);
        $contructFunction = function () use ($reflect) {
            $constructor = $reflect->getConstructor();
            if ($constructor !== null) {
                $parameters = $constructor->getParameters();
                $resolvedArgs = [];
                foreach ($parameters as $param) {
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
            } else {

                return $reflect->newInstance();
            }
        };

        $data = [
            $reflect->getName() => $contructFunction()
        ];
        self::$di_dinamic[] = $data;
    }

    public function Configuration(string $configureClass){
        $reflect = new ReflectionClass($configureClass);
        $interfaces = $reflect->getInterfaceNames();
    }

    public function EnableEndPoint()
    {
        $classes = get_declared_classes();
        self::$pathClass = $classes;
        foreach ($classes as $class) {
            $reflect = new ReflectionClass($class);
            $parent = $reflect->getParentClass();
            if (is_object($parent) && ($parent->getName() === ApiController::class)) {
                $this->mappingControllerClass(new ReflectionClass($class));
            }
        }
    }

    public function Run()
    {
        $requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        $headers = getallheaders();

        $body = file_get_contents('php://input');
        $jsonData = json_decode($body, true);

        $padrao = "/\{([^}]+)\}/";
        $varId = null;
        $indexOf = 0;
        foreach ($this->routes as $route) {

            if (preg_match($padrao, $route->path, $correspondencias)) {
                $indexOf = strpos($route->path, "{");
                $varId = substr($requestPath, $indexOf, strlen($requestPath));
                $route->path = substr($route->path, 0, $indexOf);
                $requestPath = substr($requestPath, 0, $indexOf);
            }

            if ($route->method === $requestMethod && $route->path === $requestPath) {
                if ($jsonData !== null) {
                    $req = new Request($headers, $jsonData, $varId);
                } else {

                    if ($requestMethod === "GET") {
                        $req = new Request($headers, $_GET, $varId);
                    } else {
                        $req = new Request($headers, $_POST, $varId);
                    }
                }
                $instance = $this->getInstanceBy($route->class);
                $method = $route->methodClass;
                $this->ExecuteMethod($method, $instance, $req);
                return;
            }
        }

        http_response_code(404);
        echo $this->renderError404Page();
    }

    public function ShowDependecys()
    {
        foreach (self::$di_dinamic as $key => $dependecy) {
            foreach ($dependecy as $keyintern => $dependecyExt) {
                var_dump($keyintern);
                echo "<br>";
                echo "<br>";
            }
        }
    }

    public function ShowEndPoints()
    {
        $cont = count($this->routes);
        echo "Endpoints encontrados: $cont <br>";
        foreach ($this->routes as $key => $value) {
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

    public function ShowClassLoaded(){
        foreach(self::$pathClass as $class){
            echo $class;
            echo "<br>";
        }
    }

    private function DiConteins(string $class)
    {
        $data = null;
        foreach (self::$di_dinamic as $dependecy) {
            if (isset($dependecy[$class])) {
                $data = $dependecy[$class];
                break;
            }
        }
        if ($data == null) {
            throw new Exception("Falha na injeçãa de dependecia [ {$class} ] não Registrado no Conteiner de injeção");
        }
        return $data;
    }

    private function mappingControllerClass(ReflectionClass $reflect)
    {
        $methods = $reflect->getMethods();
        foreach ($methods as $method) {
            $attributes = $method->getAttributes();
            foreach ($attributes as $attribute) {
                $atrubute_name = $attribute->getName();
                switch ($atrubute_name) {
                    case Get::class:
                        $args = $attribute->getArguments();
                        $this->addRouteGet($args[0], $reflect, $method);
                        break;
                    case Post::class:
                        $args = $attribute->getArguments();
                        $this->addRoutePost($args[0], $reflect, $method);
                        break;
                    case Delete::class:
                        $args = $attribute->getArguments();
                        $this->addRouteDelete($args[0], $reflect, $method);
                        break;
                    case Put::class:
                        $args = $attribute->getArguments();
                        $this->addRoutePut($args[0], $reflect, $method);
                        break;
                }
            }
        }
    }

    private function addRouteGet(string $Path, ReflectionClass $class, ReflectionMethod $method)
    {
        $route = new Router($Path, HttpMethod::GET, $class, $method);
        $this->routes[] = $route;
    }

    private function addRoutePost(string $Path, ReflectionClass $class, ReflectionMethod $method)
    {
        $route = new Router($Path, HttpMethod::POST, $class, $method);
        $this->routes[] = $route;
    }

    private function addRouteDelete(string $Path, ReflectionClass $class, ReflectionMethod $method)
    {
        $route = new Router($Path, HttpMethod::DELETE, $class, $method);
        $this->routes[] = $route;
    }

    private function addRoutePut(string $Path, ReflectionClass $class, ReflectionMethod $method)
    {
        $route = new Router($Path, HttpMethod::PUT, $class, $method);
        $this->routes[] = $route;
    }

    private function getInstanceBy(ReflectionClass $reflect)
    {
        $constructor = $reflect->getConstructor();
        $vars = $reflect->getProperties();
        if ($constructor !== null) {
            $parameters = $constructor->getParameters();
            if(empty($parameters)){
                return $this->injectNoContructors($reflect, $vars);
            }
            $resolvedArgs = [];
            foreach ($parameters as $param) {
                $paramClass = $param->getType();

                if ($paramClass !== null && !$paramClass->isBuiltin()) {
                    $paramClassName = $paramClass->getName();
                    $object = $this->DiConteins($paramClassName);
                    if (is_callable($object)) {
                        $resolvedArgs[] = $object();
                    } else if (is_object($object)) {
                        $resolvedArgs[] = $object;
                    }
                } else {
                    echo "Tipo primitivo ou não definidoc para conreutor de classe <br>";
                }
            }
            return $reflect->newInstanceArgs($resolvedArgs);
        } else {
            return $this->injectNoContructors($reflect, $vars);
        }
    }

    private function injectNoContructors(ReflectionClass $reflect, $vars)
    {
        $instance = $reflect->newInstance();
        if (count($vars) > 0) {
            foreach ($vars as $prop) {
                if ($this->isAnnotetionPresent($prop, Inject::class)) {
                    $propClass = $prop->getType();
                    $args = $this->getAnnotetion($prop, Inject::class);
                    if (isset($propClass)) {
                        $object = $this->DiConteins($propClass);
                        $prop->setAccessible(true);
                        $prop->setValue($instance, $object);
                    } else if (!empty($args)) {
                        $object = $this->DiConteins($args[0]);
                        $prop->setAccessible(true);
                        $prop->setValue($instance, $object);
                    } else {
                        echo "Não posso injetar algo na variavel [ {$prop->getName()} ], essa variavel tem que possuir um tipo";
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
        $this->executeMiddleware();
        try {
            $parameters = $method->getParameters();
            if ($parameters !== null) {
                $args = [];
                foreach ($parameters as $param) {
                    $paramType = $param->getType();
                    switch ($paramType) {
                        case "Daniel\Origins\Request":
                            $args[] = $req;
                            break;
                        default:
                            $args[] = null;
                            break;
                    }
                }
                $method->invokeArgs($entity, $args);
            } else {
                $method->invoke($entity);
            }
        } catch (Exception $e) {
        }
    }

    private function executeMiddleware()
    {
        foreach (self::$filterMiddlweare as $filters) {
            $filters->invokeHandle();
        }
    }

    public function executeConfig(){
        foreach(self::$pathClass as $class){
            $reflect = new ReflectionClass($class);
            $this->conteinsInterface($reflect, Configuration::class);
        }
    }

    private function conteinsInterface(ReflectionClass $class, string $interfaceExpected): bool{
        $interfaces = $class->getInterfaceNames();
        foreach($interfaces as $interface){
            if($interface == $interfaceExpected){
                $this->executeElement($class);
            }
        }
        return false;
    }

    private function executeElement(ReflectionClass $class){
        $object = $this->getInstanceBy($class);
        $object->invokeConfiguration();
        unset($object);
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
