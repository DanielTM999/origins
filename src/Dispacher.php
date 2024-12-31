<?php
    namespace Daniel\Origins;

    abstract class Dispacher{
        public abstract function map(): void;
        public abstract function dispach(DependencyManager $Dmanager): void;
        public abstract function ShowEndPoints($writeAsJson = false): void;
    }

    abstract class DispacherFactory{
        public abstract function create(): Dispacher;
    }

?>