<?php

    namespace Daniel\Origins\Annotations;

    use Attribute;

    #[Attribute(Attribute::TARGET_CLASS|Attribute::TARGET_CLASS_CONSTANT)]
    class Controller{
        public function __construct(public string $location = "") {

        }
    }

    #[Attribute(Attribute::TARGET_METHOD)]
    class Target{
        public function __construct(public string $exception) {

        }
    }
?>
