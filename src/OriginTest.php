<?php
    namespace Daniel\Origins;

    use Daniel\Origins\Tester\OriginFrameworkTest;

    if(session_status() != PHP_SESSION_ACTIVE){
        session_start();
    }

    abstract class OriginTest extends Origin {
        abstract function runTests();

        public static function initialize(bool $byTask = false): OriginTest
        {
            return new OriginFrameworkTest();
        }
    }
    
?>
