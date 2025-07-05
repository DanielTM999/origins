<?php
    namespace Daniel\Origins;

    use Daniel\Origins\Annotations\Controller;
    use Daniel\Origins\Annotations\Dependency;
    use Daniel\Origins\Aop\Aspect;
    use Override;
    use ReflectionClass;
    use RuntimeException;

    final class ServerAutoload extends Autoloader{
        public static string $metaDadosPath = "./origins.json";
        private array $loadedFiles = [];
        private array $modules = [];

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
                            }else if(in_array($item, $ignoreList)){
                                $execute = false;
                            }
                        }
                    }

                    if (preg_match('#composer|git|autoload|test|danieltm[/\\\\]origins|http-security[/\\\\]vendor#', $directory)) {
                        $execute = false;
                    }

                    if ($item === '.' || $item === '..') {
                        $execute = false;
                    }

                    if($execute){
                        $path = $directory . DIRECTORY_SEPARATOR . $item;
                        if (is_dir($path)) {
                            if (isset($this->modules[$item])) {
                                $this->modules[$item]['modulePath'] = $path;
                            }
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

        private function requireOnce($file){      
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
            $this->loadModulesConfigs();
            $this->autoloadFromDirectory($dirBase);
            $this->loadedFiles = array_reverse($this->loadedFiles);
            $this->loadedFiles = $this->loadWithDependencies($this->loadedFiles);
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
                    "modules" => $this->modules,
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

        function getInterfacesFromFile(string $file): array {
            $content = file_get_contents($file);
            $interfaces = [];

           if (preg_match('/^\s*(?:final\s+|abstract\s+)?class\s+\w+/mi', $content)) {
                if (preg_match('/implements\s+([^{\s]+)/i', $content, $matches)) {
                    $impls = explode(',', $matches[1]);
                    foreach ($impls as $impl) {
                        $interfaces[] = trim($impl);
                    }
                }
            }

            return $interfaces;
        }

        private function loadWithDependencies(array $files): array {
            $pending = $files;
            $interfaceFiles = [];
            $inOrderFiles = [];

            foreach ($pending as $file) {
                $interfaces = $this->getInterfacesFromFile($file);
                        
                if (!empty($interfaces)) {
                    $interfaceFiles[] = $file;
                    continue; 
                }

                require_once $file;
                $inOrderFiles[] = $file;
            }

            $interfaceFiles = array_unique($interfaceFiles);
            foreach ($interfaceFiles as $interfaceFile) {
                require_once $interfaceFile;
                $inOrderFiles[] = $interfaceFile;
            }
            return $inOrderFiles;
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
                    try {
                        require_once $file;
                    } catch (\Throwable $e) {
                        throw new \Exception("Erro ao carregar o arquivo '$file': possível redefinição de classe já carregada.");
                    }
                }

                $intializers = null;
                $dependecies = null;
                $controllers = null;
                $middlewares = null;
                $aspects = null;
                $controllerAdvice = null;
                $routes = [];

                $configurations = $cache["configurations"] ?? null;
                $this->modules = $cache["modules"] ?? [];
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
            // if(isset($_ENV["base.dir"])){
            //     return $_ENV["base.dir"];
            // }
            // $dirLibrary = __DIR__;

            // while (strpos($dirLibrary, 'vendor') !== false) {
            //     $dirLibrary = dirname($dirLibrary);
            // }

            // $dirBase = $dirLibrary;
            // $vendorPos = strpos($dirBase, '\vendor');
            // if ($vendorPos !== false) {
            //     $dirBase = substr($dirBase, 0, $vendorPos);
            // }
            // return $dirBase;
            return $_SERVER['DOCUMENT_ROOT'];
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
            $_SESSION["origins.modules"] = $this->modules;
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

        private function loadModulesConfigs() {
            $moduleInfoPath = $_ENV["base.dir"] . DIRECTORY_SEPARATOR . "modules.config";

            if (!file_exists($moduleInfoPath)) {
                return;
            }

            $content = file_get_contents($moduleInfoPath);
            $moduleContent = $this->extractModulesBlock($content);
            
            if ($moduleContent === null) {
                throw new RuntimeException("Bloco @modules{...} não encontrado.");
            }

            $this->modules = $this->parseModules($moduleContent);
            if (isset($this->modules['global']) && is_array($this->modules['global'])) {
                $globalProps = $this->modules['global'];
                $this->modules["origins.module.global"] = $globalProps;
                unset($this->modules['global']);

                foreach ($this->modules as &$module) {
                    foreach ($globalProps as $key => $value) {
                        if (!array_key_exists($key, $module)) {
                            $module[$key] = $value;
                        }
                    }
                }
                unset($module);
            }
        }

        private function extractModulesBlock(string $content): ?string {
            $startPos = strpos($content, '@modules{');
            if ($startPos === false) {
                return null;
            }

            $pos = $startPos + strlen('@modules{');
            $depth = 1;
            $length = strlen($content);

            for (; $pos < $length; $pos++) {
                if ($content[$pos] === '{') {
                    $depth++;
                } elseif ($content[$pos] === '}') {
                    $depth--;
                    if ($depth === 0) {
                        break;
                    }
                }
            }

            if ($depth !== 0) {
                throw new RuntimeException("Bloco @modules{ mal formado");
            }

            return substr($content, $startPos + strlen('@modules{'), $pos - ($startPos + strlen('@modules{')));
        }

        private function parseModules(string $block): array {
            $modules = [];
            $pattern = '/@(\w+)\s*(\{([^}]*)\})?/s';

            preg_match_all($pattern, $block, $matches, PREG_SET_ORDER);

            foreach ($matches as $match) {
                $moduleName = $match[1];
                $body = $match[3] ?? null;
                $config = [];

                if ($body) {
                    $lines = preg_split('/\r\n|\r|\n/', trim($body));
                    foreach ($lines as $line) {
                        if (preg_match('/^(\w+)\s*=\s*(.+)$/', trim($line), $kv)) {
                            $config[$kv[1]] = $kv[2];
                        }
                    }
                }

                $modules[$moduleName] = $config;
            }

            return $modules;
        }


    } 

?>