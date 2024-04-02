<?php
    namespace Daniel\Origins;

    abstract class Dispacher{
        public abstract function map(): void;
        public abstract function dispach(DependencyManager $Dmanager): void;
        public abstract function ShowEndPoints(): void;
    }

    abstract class DispacherFactory{
        public abstract function create(): Dispacher;
    }

?>