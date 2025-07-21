<?php


    namespace Daniel\Origins\Annotations\Serialization;

    use Attribute;

    #[Attribute(Attribute::TARGET_CLASS|Attribute::TARGET_CLASS_CONSTANT)]
    final class IgnoreNulls{}

?>