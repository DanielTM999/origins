<?php
    namespace Daniel\Origins;

    if(session_status() != PHP_SESSION_ACTIVE){
        session_start();
    }

    class Origin{
        public static bool $runBytask = false;
        private static Origin $instance;
        private Dispacher $dispacher;
        private Autoloader $autoload;
        private DependencyManager $Dmanager;
        private Config $serverConfg;
        private VarEnv $varEnvLoader;

        public function __construct()
        {
            $this->dispacher = $this->getDispacher();
            $this->autoload = $this->getAutoload();
            $this->Dmanager = $this->getDependecyManager();
            $this->serverConfg = $this->getConfigOnInit();
            $this->varEnvLoader = $this->getVarEnv();

            $this->varEnvLoader->load();
            $this->autoload->load();
            $this->dispacher->map();
            $this->Dmanager->start();
            $this->serverConfg->ConfigOnInit();

        }

        public function showMappedendPoints($writeAsJson = false){
            $this->dispacher->ShowEndPoints($writeAsJson);
        }

        public function run(){
            $this->dispacher->dispach($this->Dmanager);
        }

        private function getConfigOnInit() : Config{
            return new ServerConfig($this->Dmanager);
        }

        private function getVarEnv() : VarEnv{
            return new ServerVarEnv();
        }

        private function getDispacher(): Dispacher{
           return new ServerDispacher();
        }

        private function getAutoload(): Autoloader{
           return new ServerAutoload();
        }

        private function getDependecyManager(){
            return new DependencyManager();
        }

        public static function initialize(bool $byTask = false): Origin
        {
            self::$runBytask = $byTask;
            if (!isset(self::$instance)) {
                self::$instance = new Origin();
            }
            return self::$instance;
        }
    }

    abstract class Autoloader{
        public abstract function load(): void;
    }
?>
