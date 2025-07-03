<?php

    namespace Daniel\Origins\Annotations;

    use Attribute;

    #[Attribute(Attribute::TARGET_CLASS|Attribute::TARGET_CLASS_CONSTANT)]
    class FilterPriority{
        public function __construct(public int $priority) {

        }
    }

?>