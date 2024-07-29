<?php

    namespace Daniel\Origins;
    use Throwable;

    abstract class ControllerAdvice{
        public abstract function onError(Throwable $exception) : void;
    }

?>