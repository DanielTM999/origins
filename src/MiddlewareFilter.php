<?php
    namespace DanielTm\Origins;
    interface MiddlewareFilter{
        public function invokeHandle(): void;
    }


?>