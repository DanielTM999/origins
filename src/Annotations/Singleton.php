<?php

    namespace Daniel\Origins\Annotations;

    use Attribute;

    #[Attribute(Attribute::TARGET_CLASS|Attribute::TARGET_CLASS_CONSTANT)]
    class Singleton{
        public function __construct()
        {
            
        }
    }

?>