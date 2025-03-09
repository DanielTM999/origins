<?php

    namespace Daniel\Origins;

    use Attribute;

    #[Attribute]
    class Get {
        public function __construct(public string $value) {

        }
    }

    #[Attribute]
    class Post {
        public function __construct(public string $value) {

        }
    }

    #[Attribute]
    class Delete {
        public function __construct(public string $value) {

        }
    }

    #[Attribute]
    class Put {
        public function __construct(public string $value) {

        }
    }

    #[Attribute]
    class Patch {
        public function __construct(public string $value) {}
    }
    
?>