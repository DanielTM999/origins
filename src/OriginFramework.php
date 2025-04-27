<?php

    namespace Daniel\Origins;

    class OriginFramework extends OriginWebApp{
        protected Dispacher $dispacher;
        protected Autoloader $autoload;
        protected DependencyManager $Dmanager;
        protected Config $serverConfg;
        protected VarEnv $varEnvLoader;

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

        protected function getConfigOnInit() : Config{
            return new ServerConfig($this->Dmanager);
        }

        protected function getVarEnv() : VarEnv{
            return new ServerVarEnv();
        }

        protected function getDispacher(): Dispacher{
            return new ServerDispacher();
        }

        protected function getAutoload(): Autoloader{
            return new ServerAutoload();
        }

        protected function getDependecyManager(): DependencyManager{
            return new ServerDependencyManager();
        }

    }


?>