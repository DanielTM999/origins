<?php

    namespace Daniel\Origins;

    use Attribute;

    #[Attribute]
    class Qualifier{
        public function __construct(string $name = "default") {
            
        }   
    }
?>