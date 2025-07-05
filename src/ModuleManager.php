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
        function getCallableFilePath(): string;
        function getCallableFileName(bool $withExtension = true): string;
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

        function getModuleProperty(string $key, array|string $toReplace = "", int $depth = 0){
            if ($depth > 10) {
                throw new \RuntimeException("Recursion depth exceeded while resolving module property: $key");
            }
            $value = $this->moduleArray[$key] ?? null;
            if (!is_string($value)) {
                return $value;
            }

            $value = preg_replace_callback('/\$\{?env\["([^"\]]+)"\]\}?/', function ($matches) {
                $envKey = $matches[1];
                return $_ENV[$envKey] ?? '';
            }, $value);
            
            $value = preg_replace_callback('/\$\{?module\["([^"\]]+)"\]\}?/', function ($matches) use ($depth) {
                $moduleKey = $matches[1];
                return $this->getModuleProperty($moduleKey, "", $depth + 1) ?? '';
            }, $value);

            if (!empty($toReplace)) {
                if (is_string($toReplace)) {
                    $value = preg_replace('/\[\?\]/', $toReplace, $value);
                } elseif (is_array($toReplace)) {
                    foreach ($toReplace as $replacement) {
                        $value = preg_replace('/\[\?\]/', $replacement, $value, 1);
                    }
                }
            }

            return $value;
        }

        public function getModuleAsJson(): string {
            return json_encode([
                'name' => $this->getModuleName(),
                'path' => $this->getModulePath(),
                'definedProperties' => $this->moduleArray
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }

        function getCallableFilePath(int $depth = 1): string{
            $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $depth + 1);
            if (isset($backtrace[$depth]['file'])) {
                return $backtrace[$depth]['file'];
            }
            return 'unknown';
        }

        function getCallableFileName(bool $withExtension = true): string{
            $filePath = $this->getCallableFilePath(2);
            if($withExtension){
                return basename($filePath);
            }else{
                return pathinfo($filePath, PATHINFO_FILENAME);
            }
        }


    }

    final class ModuleManager
    {

        public static function isModuleAvailable(): bool{
            return isset($_SESSION["origins.modules"]) && !empty($_SESSION["origins.modules"] ?? []);
        }

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

        public static function getModuleByName(string $moduleNameTarget): Module|null{
            $modules = $_SESSION["origins.modules"] ?? [];
            foreach ($modules as $moduleName => $data) {
                if($moduleNameTarget === $moduleName) {
                    return new ModuleInfo($moduleName, $data);
                };
            }
            return null;
        }

        public static function getModuleByReference($target): Module|null{
            $filename = self::getFileForObject($target);
            
            return $filename !== false
                ? self::resolveModuleFromFile($filename)
                : null;
        }

        public static function getModules() : array{
            return $_SESSION["origins.modules"] ?? [];
        }

        public static function getGlobalModuleProperty(string $key, array|string $toReplace = "", int $depth = 0){
            $moduleArray = $_SESSION["origins.modules"]["origins.module.global"] ?? [];
            if ($depth > 10) {
                throw new \RuntimeException("Recursion depth exceeded while resolving module property: $key");
            }
            $value = $moduleArray[$key] ?? null;
            if (!is_string($value)) {
                return $value;
            }

            $value = preg_replace_callback('/\$\{?env\["([^"\]]+)"\]\}?/', function ($matches) {
                $envKey = $matches[1];
                return $_ENV[$envKey] ?? '';
            }, $value);
            
            $value = preg_replace_callback('/\$\{?module\["([^"\]]+)"\]\}?/', function ($matches) use ($depth) {
                $moduleKey = $matches[1];
                return self::getGlobalModuleProperty($moduleKey, "", $depth + 1) ?? '';
            }, $value);

            if (!empty($toReplace)) {
                if (is_string($toReplace)) {
                    $value = preg_replace('/\[\?\]/', $toReplace, $value);
                } elseif (is_array($toReplace)) {
                    foreach ($toReplace as $replacement) {
                        $value = preg_replace('/\[\?\]/', $replacement, $value, 1);
                    }
                }
            }

            return $value;
        }

        private static function getStandardModule(array $backtrace): Module|null{
            foreach ($backtrace as $frame) {
                if (isset($frame['file'])) {
                    $module = self::resolveModuleFromFile($frame['file']);
                    if($module != null) return $module;
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
            } else if(is_string($object)){
                return $object;
            }elseif (is_object($object)) {
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