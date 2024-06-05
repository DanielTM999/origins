<?php
    namespace Daniel\Origins;

    use Override;

    class ServerVarEnv extends VarEnv{

        private static $PROJECT_ENV;

        public function __construct()
        {
            $projectEnv = __DIR__;

            $dirLibrary = __DIR__;

            while (strpos($dirLibrary, 'vendor') !== false) {
                $dirLibrary = dirname($dirLibrary);
            }
            
            $projectEnv = $dirLibrary;

            self::$PROJECT_ENV = $projectEnv;

        }

        #[Override]
        public function load(): void{
            $envFilePath = self::$PROJECT_ENV . '/.env';
            if (!file_exists($envFilePath)) {
                touch($envFilePath);
            }

            $this->readEnv($envFilePath);
        }
        

        private function readEnv(string $filePath){
            $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line){
                if($line[0] != "#"){
                    if (strpos($line, '=') !== false) {
                        list($key, $value) = explode('=', $line, 2);
                        $key = trim($key);
                        $value = trim($value);
                        $value = $this->removeAspas($value);
                        if (!empty($key)) {
                            $_ENV[$key] = $value;
                        }
                    }
                }
            }

        }

        private function removeAspas(string $word){
            return str_replace('"', '', $word);
        }
    }
?>