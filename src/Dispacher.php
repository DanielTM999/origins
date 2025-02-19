<?php
    namespace Daniel\Origins;

    use ReflectionClass;
    use ReflectionMethod;

    abstract class Dispacher{
        public abstract function map(): void;
        public abstract function dispach(DependencyManager $Dmanager): void;
        public abstract function ShowEndPoints($writeAsJson = false): void;
        public abstract function addExternalRoute(Router $route): void;
        public abstract function addExternalRouteAtrubutes(string $path, string $httpMethod, ReflectionClass $class, ReflectionMethod $method): void;

    }

    abstract class DispacherFactory{
        public abstract function create(): Dispacher;
    }

?>