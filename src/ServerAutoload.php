<?php
    namespace Daniel\Origins;
    use Override;
    use ReflectionClass;

    final class ServerAutoload extends Autoloader{
        public static string $metaDadosPath = "./origins.json";
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
                    $this->loadElements(false);
                }
            }else{
                $this->loadElements(false);
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
                if (!in_array($file, $this->loadedFiles) && !($file === $_ENV["base.dir"]."\\index.php")) {
                    $this->loadedFiles[] = $file;
                }
            } catch (\Throwable $th) {
                echo $th->getMessage();
            }
        }

        private function loadElements(bool $addCache = true){
            $dirBase = $this->getBaseDir();
            $_ENV["base.dir"] = $dirBase;
            $this->autoloadFromDirectory($dirBase);
            $this->loadedFiles = array_reverse($this->loadedFiles);
            foreach($this->loadedFiles as $file){
                require_once $file;
            }

            $classes = get_declared_classes();
            $configurations = [];
            $controllers = [];
            $dependecies = [];
            $middlewares = [];
            $aspects = [];
            $controllerAdvice = "";
            foreach ($classes as $class){
                $reflect = new ReflectionClass($class);
                $parentClass = $reflect->getParentClass();
                $atributeController = $reflect->getAttributes(Controller::class);
                $atrbuteDependency = $reflect->getAttributes(Dependency::class);

                if ($parentClass !== false) {
                    $parentClassName = $parentClass->getName();
                    if($parentClassName === OnInit::class){
                        $configurations[] = $class;
                        $dependecies[] = $class;
                    }else if($parentClassName === ControllerAdvice::class){
                        $controllerAdvice = $class;
                    }else if($parentClassName === Middleware::class){
                        $middlewares[] = $class;
                    }else if($parentClassName === Aspect::class){
                        $aspects[] = $class;
                    }
                }

                if (isset($atributeController) && !empty($atributeController)){
                    $controllers[] = $class;
                }

                if (isset($atrbuteDependency) && !empty($atrbuteDependency)){
                    $dependecies[] = $class;
                }

            }

            if($addCache){
                $this->addCache([
                    "baseDir" => $dirBase,
                    "loadedFiles" => $this->loadedFiles,
                    "configurations" => [
                        "initializers" => $configurations,
                        "middlewares" => $middlewares,
                        "aspects" => $aspects,
                        "controllers" => $controllers,
                        "dependecies" => $dependecies,
                        "controllerAdvice" => $controllerAdvice,
                        "routes" => []
                    ],
                ]);
            }
            $this->setSessionsCash($dependecies, $controllers, $configurations, $middlewares, $controllerAdvice, $aspects, []);
        }

        private function loadElementsByCache($cache){
            if(isset($cache["baseDir"])){
                $baseDir = $cache["baseDir"];
                $_ENV["base.dir"] = $baseDir;
            }else{
                $_ENV["base.dir"] = $this->getBaseDir();
            }
            if(isset($cache["loadedFiles"])){
                $loadedFiles = $cache["loadedFiles"];
                foreach($loadedFiles as $file){
                    require_once $file;
                }

                $intializers = null;
                $dependecies = null;
                $controllers = null;
                $middlewares = null;
                $aspects = null;
                $controllerAdvice = null;
                $routes = [];

                $configurations = $cache["configurations"] ?? null;
                if(isset($configurations)){
                    $intializers = $configurations["initializers"] ?? null;
                    $dependecies = $configurations["dependecies"] ?? null;
                    $controllers = $configurations["controllers"] ?? null;
                    $middlewares = $configurations["middlewares"] ?? null;
                    $aspects = $configurations["aspects"] ?? null;
                    $controllerAdvice = $configurations["controllerAdvice"] ?? null;
                    $routes = $configurations["routes"] ?? [];
                }

                $this->setSessionsCash($dependecies, $controllers, $intializers, $middlewares, $controllerAdvice, $aspects, $routes);
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
            $pos = strpos($directory, "thread_task") !== false;
            $posCLI = strpos($directory, "Origins_") !== false;

            if(Origin::$runBytask){
                $task = strpos($directory, "index.php") !== false;
                if($task){
                    return true;
                }
            }else if($posCLI){
                return true;
            }

            if ($pos){
                return true;
            }

            return (preg_match('/\b(views?)\b/', $directory));
        }

        private function getCache(){
            $filePath = self::$metaDadosPath;
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
            file_put_contents(self::$metaDadosPath, $jsonData);
        }

        private function setSessionsCash($dependecies, $controllers, $initializers, $middlewares, $controllerAdvice, $aspects, $routes = []){
            $controllerAdvice = ($controllerAdvice === "") ? null : $controllerAdvice;
            $_SESSION["origins.dependencys"] = $dependecies;
            $_SESSION["origins.controllers"] = $controllers;
            $_SESSION["origins.initializers"] = $initializers;
            $_SESSION["origins.middlewares"] = $middlewares;
            $_SESSION["origins.controllerAdvice"] = $controllerAdvice;
            $_SESSION["origins.files"] = $this->loadedFiles;
            $_SESSION["origins.aspects"] = $aspects;
            $_SESSION["origins.loaders"] = [
                "dependencys" => $dependecies,
                "controllers" => $controllers,
                "initializers" => $initializers,
                "middlewares" => $middlewares,
                "controllerAdvice" => $controllerAdvice,
                "aspects" => $aspects,
                "routes" => $routes
            ];
        }
    } 

?>