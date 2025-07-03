<?php

    namespace Daniel\Origins\Annotations;

    use Attribute;

    #[Attribute]
    class Qualifier{
        public function __construct(string $name = "default") {
            
        }   
    }
?>