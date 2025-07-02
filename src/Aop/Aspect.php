<?php

    namespace Daniel\Origins\Aop;

    use ReflectionMethod;

    abstract class Aspect
    {
        public function __construct() {}
        abstract public function pointCut(object &$controllerEntity, ReflectionMethod &$method, array &$varArgs): bool;
        abstract public function aspectBefore(object &$controllerEntity, ReflectionMethod &$method, array &$varArgs);
        abstract public function aspectAfter(object &$controllerEntity, ReflectionMethod &$method, array &$varArgs, mixed &$result);
    }
    
?>