<?php

    namespace Daniel\Origins\Annotations;

    use Attribute;
    
    
     #[Attribute]
    final class PathVariable {
        public function __construct(public string $name = "", public bool $required = true)
        {
        	
        }
     }
?>