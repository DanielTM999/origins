<?php

    namespace Daniel\Origins;

    if(session_status() != PHP_SESSION_ACTIVE){
        session_start();
    }

    final class OriginFrameworkTest extends OriginTest{
        private Autoloader $autoload;
        private DependencyManager $Dmanager;
        private Config $serverConfg;
        private VarEnv $varEnvLoader;

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

        private function getConfigOnInit() : Config{
            return new ServerConfig($this->Dmanager);
        }

        private function getVarEnv() : VarEnv{
            return new ServerVarEnv();
        }

        private function getAutoload(): Autoloader{
            return new ServerAutoload();
        }

        private function getDependecyManager(): DependencyManager{
            return new ServerDependencyManager();
        }

    }


?>