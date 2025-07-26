<?php

    namespace Daniel\Origins\Annotations\Serialization;

    use Attribute;

    #[Attribute(Attribute::TARGET_PROPERTY)]
    final class ListOf
    {
        public function __construct(public string $class) {
            if (empty($class)) {
                throw new \InvalidArgumentException('A classe da lista não pode ser vazia.');
            }
        }
    }
    
?>