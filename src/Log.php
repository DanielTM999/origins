<?php
    namespace Daniel\Origins;

    use RuntimeException;

    class Log{
        
        public static function info($message, string $filename = "App.log"): void{
            $message = self::serializateMessage($message);
            self::write($message, "[INFO]", $filename);
        }

        public static function waring($message, string $filename = "App.log"): void{
            $message = self::serializateMessage($message);
            self::write($message, "[WARING]", $filename);
        }

        public static function error($message, string $filename = "App.log"): void{
            $message = self::serializateMessage($message);
            self::write($message, "[ERROR]", $filename);
        }

        private static function write(string $message, string $type, string $filename = "App.log"){
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
            $logMessage = date('Y-m-d H:i:s') . " " . $type . " " . $message . PHP_EOL;
            if (file_put_contents($logFile, $logMessage, FILE_APPEND) === false) {
                throw new RuntimeException("Failed to write to log file: $logFile");
            }
        }

        private static function serializateMessage($message){
            if(!isset($message)){
                return "null";
            }else if (is_array($message)) {
                return json_encode($message); 
            } elseif (is_object($message)) {
                if (self::isSerializable($message)) {
                    return serialize($message);
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