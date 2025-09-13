<?php
    namespace Daniel\Origins;

    abstract class Origin{
        public static bool $runBytask = false;
        protected static Origin $instance;

        abstract function run();

        public static function initialize(bool $byTask = false): Origin
        {
            self::$runBytask = $byTask;
            if (!isset(self::$instance)) {
                self::$instance = new OriginFramework();
            }
            return self::$instance;
        }
     
        public static function initializeTestEnvaroment(): Origin{
            return new OriginFrameworkTest();
        }

    }

?>
