<?php

    namespace Daniel\Origins;

use ReflectionClass;

    interface DependencyManager
    {
        function load(): void;
        function addDependency(string $dependency, object &$object, string $qualifier = "default"): void;
        function getDependency(string $dependency, string $qualifier = "default"): object|null;
        function showDependencys(): string;
        function tryCreate(string|ReflectionClass $class): object|null;
    }
    

?>