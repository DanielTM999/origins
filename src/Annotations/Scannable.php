<?php

    namespace Daniel\Origins\Annotations;

    use Attribute;

    #[Attribute]
    class Scannable{
        public string $name;

        public function __construct(string $name) {
            $this->name = $name;
        } 
    }
?>