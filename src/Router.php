<?php
    namespace DanielTm\Origins;

    use ReflectionClass;
    use ReflectionMethod;

    class Router{
        public string $method;
        public string $path;
        public ReflectionClass $class;
        public ReflectionMethod $methodClass;

        public function __construct(string $path, string $method, ReflectionClass $class,  ReflectionMethod $methodClass)
        {
            $this->path = $path;
            $this->method = $method;
            $this->class = $class;
            $this->methodClass = $methodClass;
        }
    }


    class HttpMethod{
        const GET = "GET";
        const POST = "POST";
        const DELETE = "DELETE";
        const PUT = "PUT";
    } 
?>