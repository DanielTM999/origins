<?php
    namespace Daniel\Origins;
    use Override;

    class ServerAutoload extends Autoloader{
        private array $loadedFiles = [];

        #[Override]
        public function load(): void{
            $dirLibrary = __DIR__;

            while (strpos($dirLibrary, 'vendor') !== false) {
                $dirLibrary = dirname($dirLibrary);
            }

            $dirBase = $dirLibrary;
            $vendorPos = strpos($dirBase, '\vendor');
            if ($vendorPos !== false) {
                $dirBase = substr($dirBase, 0, $vendorPos);
            }
            $_ENV["base.dir"] = $dirBase;
            $this->autoloadFromDirectory($dirBase);
            $this->loadedFiles = array_reverse($this->loadedFiles);
            foreach($this->loadedFiles as $file){
                require_once $file;
            }
        }

        private function autoloadFromDirectory($directory){
            $items = scandir($directory);

            foreach ($items as $item) {
                try {
                    $execute = true;
                    if(isset($_ENV["load.ignore"])){
                        $ignore = $_ENV["load.ignore"];
                        $ignoreList = explode("@", $ignore);
                        foreach($ignoreList as $v){
                            $v = str_replace('/', '\\', $v);
                            if(strpos($directory, $v) !== false){
                                $execute = false;
                            }
                        }
                    }
                    if (strpos($directory, "composer") !== false || strpos($directory, "git") !== false || strpos($directory, "autoload") !== false || strpos($directory, "danieltm/origins" ) !== false || strpos($directory, "http-security\\vendor") !== false) {
                        $execute = false;
                    }
                    if ($item === '.' || $item === '..') {
                        $execute = false;
                    }

                    if($execute){
                        $path = $directory . DIRECTORY_SEPARATOR . $item;
                        if (is_dir($path)) {
                            $this->autoloadFromDirectory($path);
                        } elseif (is_file($path) && pathinfo($path, PATHINFO_EXTENSION) === 'php' && !($this->containsClassView($path))) {
                            $this->requireOnce($path);
                        }
                    }
                } catch (\Throwable $th) {
                    echo $th->getMessage();
                }

            }
        }

        private function requireOnce($file)
        {      
            try{
                if (!in_array($file, $this->loadedFiles)) {
                    $this->loadedFiles[] = $file;
                }
            } catch (\Throwable $th) {
                echo $th->getMessage();
            }
        }

        private function containsClassView($directory) {
            return strpos($directory, "views") !== false || strpos($directory, "view") !== false;
        }
    } 

?>