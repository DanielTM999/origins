<?php
    namespace Daniel\Origins;
    use Override;

    class ServerAutoload extends Autoloader{
        private array $loadedFiles = [];

        #[Override]
        public function load(): void{
            $dirBase = pathinfo(__DIR__, 1);
            $this->autoloadFromDirectory($dirBase);
        }

        private function autoloadFromDirectory($directory){
            $items = scandir($directory);

            foreach ($items as $item) {
                if ($item === '.' || $item === '..') {
                    continue;
                }
                $path = $directory . DIRECTORY_SEPARATOR . $item;
                if (is_dir($path)) {
                    $this->autoloadFromDirectory($path);
                } elseif (is_file($path) && pathinfo($path, PATHINFO_EXTENSION) === 'php') {
                    $this->requireOnce($path);
                }
            }
        }

        private function requireOnce($file)
        {
            
            if (!in_array($file, $this->loadedFiles)) {
                require_once $file;
                $this->loadedFiles[] = $file;
            }
        }
    } 

?>