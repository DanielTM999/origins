<?php

    namespace Daniel\Origins;

    use ReflectionMethod;

    abstract class Aspect
    {
        public function __construct() {}
        abstract public function aspectBefore(object &$controllerEntity, ReflectionMethod &$method, array &$varArgs);
    }
    
?>