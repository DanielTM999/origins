<?php

    namespace Daniel\Origins;

    use Attribute;

    #[Attribute(Attribute::TARGET_CLASS|Attribute::TARGET_CLASS_CONSTANT)]
    class Dependency{
        public function __construct(public bool $singleton = false)
        {
            
        }
    }

    #[Attribute]
    class Inject{
        public function __construct(string $class = null) {
            
        }   
    }
?>