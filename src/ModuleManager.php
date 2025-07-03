<?php
    namespace Daniel\Origins;

use InvalidArgumentException;
use ReflectionClass;
use ReflectionMethod;
use ReflectionObject;

    interface Module{
        function getModuleName(): string;
        function getModulePath(): string;
        function getModuleProperty(string $key);
        function getModuleAsJson(): string;
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
            $value = $this->moduleArray[$key] ?? null;
            if (!is_string($value)) {
                return $value;
            }

            return preg_replace_callback('/\$\{?env\["([^"\]]+)"\]\}?/', function ($matches) {
                $envKey = $matches[1];
                return $_ENV[$envKey] ?? '';
            }, $value);
        }

        public function getModuleAsJson(): string {
            return json_encode([
                'name' => $this->getModuleName(),
                'path' => $this->getModulePath(),
                'definedProperties' => $this->moduleArray
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }
    }

    final class ModuleManager
    {

        public static function getCurrentModule(object|null $object = null) : Module|null{
            try{
                if($object == null){
                    $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
                    return self::getStandardModule($backtrace);
                }else{
                    return self::getObjectModule($object);
                }
            }catch(\Throwable $t){
                return null;
            }
        }

        public static function getModules() : array{
            return $_SESSION["origins.modules"] ?? [];
        }

        private static function getStandardModule(array $backtrace): Module|null{
            foreach ($backtrace as $frame) {
                if (isset($frame['file'])) {
                    return self::resolveModuleFromFile($frame['file']);
                }
            }
            return null;
        }

        private static function getObjectModule(object $object): Module|null{
            $filename = self::getFileForObject($object);
            return $filename !== false
                ? self::resolveModuleFromFile($filename)
                : null;
        }

        private static function getFileForObject($object): string{
            if ($object instanceof ReflectionMethod) {
                return $object->getDeclaringClass()->getFileName();
            } elseif ($object instanceof ReflectionClass) {
                return $object->getFileName();
            } elseif ($object instanceof ReflectionObject) {
                return $object->getFileName();
            } elseif (is_object($object)) {
                $reflection = new ReflectionObject($object);
                return $reflection->getFileName();
            }

            throw new InvalidArgumentException("Argumento inválido para getFileForObject");
        }

        private static function resolveModuleFromFile(string $filename): Module|null {
            $path = realpath(dirname($filename));
            if ($path === false) return null;

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

            return null;
        }


    }
    

?>