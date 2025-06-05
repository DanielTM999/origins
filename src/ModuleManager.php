<?php
    namespace Daniel\Origins;

    interface Module{
        function getModuleName(): string;
        function getModulePath(): string;
        function getModuleProperty(string $key);
    }

    final class ModuleInfo implements Module{

        private $moduleArray;
        private $realName;

        function __construct($realName, $moduleArray){
            $this->moduleArray = $moduleArray;
            $this->realName = $realName;
        }

        function getModuleName(): string {
            return $this->moduleArray["name"] ?? $this->realName;
        }

        function getModulePath(): string{
            return $this->moduleArray["modulePath"] ?? "detached Module";
        }

        function getModuleProperty(string $key){
            return $this->moduleArray[$key] ?? null;
        }
    }

    final class ModuleManager
    {
        public static function getCurrentModule() : Module|null{
            $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
            $callerFile = null;

            foreach ($backtrace as $frame) {
                if (isset($frame['file'])) {
                    $callerFile = $frame['file'];
                    break;
                }
            }

            if ($callerFile !== null) {
                $path = realpath(dirname($callerFile));
                $modules = $_SESSION["origins.modules"] ?? [];

                foreach ($modules as $moduleName => $data) {
                    if (isset($data['modulePath'])) {
                        $modulePath = realpath($data['modulePath']);
                        if ($modulePath !== false && strpos($path, $modulePath) === 0) {
                            $nextChar = substr($path, strlen($modulePath), 1);
                            if ($nextChar === '' || $nextChar === DIRECTORY_SEPARATOR) {
                                return new ModuleInfo($moduleName, $data);
                            }
                        }
                    }
                }
            }

            return null;
        }

    }
    

?>