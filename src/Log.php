<?php
    namespace Daniel\Origins;

    use Daniel\Origins\Serialization\JsonObject;
    use RuntimeException;
    use Throwable;

    class Log{

        private const DEFAULT_LEVEL = 'INFO';

        private const LEVELS = [
            'TRACE' => 100,
            'DEBUG' => 200,
            'INFO' => 300,
            'WARNING' => 400,
            'ERROR' => 500,
            'OFF' => 600,
        ];

        public static function trace($message, string $filename = "App.log"): void{
            self::log($message, 'TRACE', $filename);
        }

        public static function debug($message, string $filename = "App.log"): void{
            self::log($message, 'DEBUG', $filename);
        }
        
        public static function info($message, string $filename = "App.log"): void{
            self::log($message, 'INFO', $filename);
        }

        public static function warning($message, string $filename = "App.log"): void{
            self::log($message, 'WARNING', $filename);
        }

        public static function waring($message, string $filename = "App.log"): void{
            self::warning($message, $filename);
        }

        public static function error(
            $message,
            string $filename = "App.log",
            ?Throwable $exception = null
        ): void{
            if ($exception !== null) {
                $message = self::serializateMessage($message)
                    . PHP_EOL
                    . (string) $exception;
            }

            self::log($message, 'ERROR', $filename);
        }

        public static function isEnabled(string $level): bool{
            $level = self::normalizeLevel($level);
            $configuredLevel = self::configuredLevel();

            return $level !== 'OFF'
                && $configuredLevel !== 'OFF'
                && self::LEVELS[$level] >= self::LEVELS[$configuredLevel];
        }

        public static function log(
            $message,
            string $level,
            string $filename = "App.log"
        ): void{
            if (!self::isEnabled($level)) {
                return;
            }

            $message = self::serializateMessage($message);
            self::write($message, $level, $filename);
        }

        private static function write(string $message, string $level, string $filename = "App.log"){
            if (pathinfo($filename, PATHINFO_EXTENSION) !== 'log') {
                $filename .= '.log';
            }
            $pathLog = $_ENV["log.path"] ?? "";
            if (!empty($pathLog)) {
                if (substr($pathLog, 0, 1) !== '/') {
                    $pathLog = '/' . $pathLog;
                }
                $pathLog = rtrim($pathLog, '/');
            }

            $baseDir = $_ENV["base.dir"] ?? "";
            if (empty($baseDir)) {
                throw new RuntimeException("Base directory is not defined in the environment.");
            }

            $logDir = $baseDir . $pathLog;
            if (!is_dir($logDir)) {
                mkdir($logDir, 0777, true);
            }

            if (!is_writable($logDir)) {
                throw new RuntimeException("The directory $logDir is not writable.");
            }

            $filename = ltrim($filename, '/');
            $logFile = "$logDir/$filename";
            if (!file_exists($logFile)) {
                touch($logFile);
            }
            $logMessage = date('Y-m-d H:i:s') . " [" . $level . "] " . $message . PHP_EOL;
            if (file_put_contents($logFile, $logMessage, FILE_APPEND) === false) {
                throw new RuntimeException("Failed to write to log file: $logFile");
            }
        }

        private static function configuredLevel(): string{
            $configuredLevel = (string) ($_ENV['log.level'] ?? self::DEFAULT_LEVEL);

            try {
                return self::normalizeLevel($configuredLevel);
            } catch (\InvalidArgumentException $exception) {
                return self::DEFAULT_LEVEL;
            }
        }

        private static function normalizeLevel(string $level): string{
            $level = strtoupper(trim($level));
            $aliases = [
                'WARN' => 'WARNING',
                'WARING' => 'WARNING',
                'NONE' => 'OFF',
            ];
            $level = $aliases[$level] ?? $level;

            if (!isset(self::LEVELS[$level])) {
                throw new \InvalidArgumentException(
                    "Invalid log level '$level'. Use TRACE, DEBUG, INFO, WARNING, ERROR or OFF."
                );
            }

            return $level;
        }

        private static function serializateMessage($message){
            if(!isset($message)){
                return "null";
            }elseif (is_bool($message)) {
                return $message ? 'true' : 'false';
            }else if (is_array($message)) {
                return json_encode($message); 
            } elseif (is_object($message)) {
                try{
                    return JsonObject::defaultSerialization($message);
                }catch(\Exception $e){
                    if (self::isSerializable($message)) {
                        return serialize($message);
                    }
                }
                return "[unserializable object of type " . get_class($message) . "]";
            }else{
                return $message;
            }
        }

        private static function isSerializable(object $obj): bool {
            static $nonSerializable = [
                'ReflectionClass',
                'ReflectionObject',
                'ReflectionMethod',
                'Closure',
                'PDO',
                'PDOStatement',
                'mysqli',
                'resource',
            ];

            foreach ($nonSerializable as $class) {
                if ($obj instanceof $class) {
                    return false;
                }
            }

            try {
                serialize($obj);
                return true;
            } catch (\Throwable $e) {
                return false;
            }
        }

    }

?>
