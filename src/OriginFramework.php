<?php

    namespace Daniel\Origins;

    final class OriginFramework extends OriginWebApp{
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
            $this->Dmanager->load();

            $this->Dmanager->addDependency(Dispacher::class, $this->dispacher);

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

        private function getDependecyManager(): DependencyManager{
            return new ServerDependencyManager();
        }

    }


?>