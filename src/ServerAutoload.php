<?php
    namespace Daniel\Origins;
    use Override;

    class ServerAutoload extends Autoloader{
        private array $loadedFiles = [];

        #[Override]
        public function load(): void{

            if(isset($_ENV["enviroment"])){
                $enviroment = $_ENV["enviroment"];
                if($enviroment === "prod" || $enviroment === "production"){
                    $autoload = $this->getCache();
                    if(isset($autoload)){
                        $this->loadElementsByCache($autoload);
                    }else{
                        $this->loadElements();
                    }
                }else{
                    $this->loadElements();
                }
            }else{
                $this->loadElements();
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

        private function loadElements(){
            $dirBase = $this->getBaseDir();
            $_ENV["base.dir"] = $dirBase;
            $this->autoloadFromDirectory($dirBase);
            $this->loadedFiles = array_reverse($this->loadedFiles);
            foreach($this->loadedFiles as $file){
                require_once $file;
            }

            $this->addCache([
                "baseDir" => $dirBase,
                "loadedFiles" => $this->loadedFiles
            ]);
        }

        private function loadElementsByCache($cache){
            $baseDir = $cache["baseDir"];
            if(isset($baseDir)){
                $_ENV["base.dir"] = $baseDir;
            }else{
                $_ENV["base.dir"] = $this->getBaseDir();
            }
            $loadedFiles = $cache["loadedFiles"];
            if(isset($loadedFiles)){
                foreach($loadedFiles as $file){
                    require_once $file;
                }
            }else{
                $this->loadElements();
            }
        }

        private function getBaseDir(): string{
            $dirLibrary = __DIR__;

            while (strpos($dirLibrary, 'vendor') !== false) {
                $dirLibrary = dirname($dirLibrary);
            }

            $dirBase = $dirLibrary;
            $vendorPos = strpos($dirBase, '\vendor');
            if ($vendorPos !== false) {
                $dirBase = substr($dirBase, 0, $vendorPos);
            }
            return $dirBase;
        }

        private function containsClassView($directory) {
            return preg_match('/\bviews?\b/', $directory);
        }

        private function getCache(){
            $filePath = './autoload.json';
            if (!file_exists($filePath)) {
                return null;
            }
            $jsonData = file_get_contents($filePath);
            if ($jsonData === false) {
                return null;
            }
            $data = json_decode($jsonData, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return null;
            }
            return $data;
        }

        private function addCache($settings){
            $jsonData = json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            $filePath = './autoload.json';

            file_put_contents($filePath, $jsonData);
        }
    } 

?>