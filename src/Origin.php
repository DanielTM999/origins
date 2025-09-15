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

        public static function getRuntimeDir($create = true): string{
            $baseDir = $_ENV["base.dir"];
            $runtimeDir = "$baseDir".DIRECTORY_SEPARATOR."runtime".DIRECTORY_SEPARATOR;

            if ($create && !is_dir($runtimeDir)) {
                mkdir($runtimeDir, 0777, true);
            }

            return $runtimeDir;
        }
     
        public static function initializeTestEnvaroment(): Origin{
            return new OriginFrameworkTest();
        }

    }

?>
