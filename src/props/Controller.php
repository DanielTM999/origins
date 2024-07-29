<?php

    namespace Daniel\Origins;

    use Attribute;

    #[Attribute(Attribute::TARGET_CLASS|Attribute::TARGET_CLASS_CONSTANT)]
    class Controller{}

    #[Attribute(Attribute::TARGET_CLASS|Attribute::TARGET_CLASS_CONSTANT)]
    class ControllerAdvice{}

    #[Attribute(Attribute::TARGET_METHOD)]
    class Target{
        public function __construct(public string $exception) {

        }
    }
?>