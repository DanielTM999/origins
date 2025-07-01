<?php
    namespace Daniel\Origins\proxy;

    class ObjectInterceptor{
        public function invoke(object &$target, string $method, array &$args){
            return $target->$method(...$args);
        }
    }

?>