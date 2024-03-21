<?php
    namespace DanielTm\Origins;

    use Attribute;

    #[Attribute]
    class Inject{
        public function __construct(string $class = null) {

        }
    }

?>