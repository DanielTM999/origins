<?php

    namespace Daniel\Origins;

    if(session_status() != PHP_SESSION_ACTIVE){
        session_start();
    }

    class OriginFrameworkTest extends OriginTest{
        protected Autoloader $autoload;
        protected DependencyManager $Dmanager;
        protected Config $serverConfg;
        protected VarEnv $varEnvLoader;

        public function __construct()
        {
            $this->autoload = $this->getAutoload();
            $this->Dmanager = $this->getDependecyManager();
            $this->serverConfg = $this->getConfigOnInit();
            $this->varEnvLoader = $this->getVarEnv();

            $this->varEnvLoader->load();
            $this->autoload->load();
            $this->Dmanager->load();

            $this->serverConfg->ConfigOnInit();

        }

        public function showMappedendPoints($writeAsJson = false){
            
        }

        public function run(){
           $this->runTests();
        }

        public function runTests(){
            echo "Rodando testes";
        }

        protected function getConfigOnInit() : Config{
            return new ServerConfig($this->Dmanager);
        }

        protected function getVarEnv() : VarEnv{
            return new ServerVarEnv();
        }

        protected function getAutoload(): Autoloader{
            return new ServerAutoload();
        }

        protected function getDependecyManager(): DependencyManager{
            return new ServerDependencyManager();
        }

    }


?>