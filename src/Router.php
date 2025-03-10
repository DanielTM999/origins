<?php

    namespace Daniel\Origins;

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

        
        public function __toString()
        {
            return "Router Information:\n" .
                "Path: {$this->path}\n" .
                "Method: {$this->method}\n" .
                "Class: {$this->class->getName()}\n" .
                "Method Class: {$this->methodClass->getName()}";
        }
    }

?>