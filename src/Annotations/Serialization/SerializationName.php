<?php


    namespace Daniel\Origins\Annotations\Serialization;

    use Attribute;

    #[Attribute(Attribute::TARGET_PROPERTY)]
    final class SerializationName{
        public function __construct(public string $name) {}
    }

?>