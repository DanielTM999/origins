<?php

    namespace Daniel\Origins\Exceptions;
    
    use Exception;
    use InvalidArgumentException;
    use Throwable;
    
    class RequiredPathVariableException extends InvalidArgumentException {
        
        public function __construct(string $message = "", int $code = 422)
        {
            parent::__construct($message, $code);
        }
    }
    
?>
