<?php
    namespace Daniel\Origins;


    class Origin{
        private static Origin $instance;
        private Dispacher $dispacher;
        private Autoloader $autoload;
        private DependencyManager $Dmanager;
        private Config $serverConfg;

        public function __construct()
        {
            $this->dispacher = $this->getDispacher();
            $this->autoload = $this->getAutoload();
            $this->Dmanager = $this->getDependecyManager();
            $this->serverConfg = $this->getConfigOnInit();

            $this->autoload->load();
            $this->dispacher->map();
            $this->Dmanager->start();
            $this->serverConfg->ConfigOnInit();
        }

        public function showMappedendPoints(){
            $this->dispacher->ShowEndPoints();
        }

        public function run(){
            $this->dispacher->dispach($this->Dmanager);
        }

        private function getConfigOnInit() : Config{
            return new ServerConfig($this->Dmanager);
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

        public static function initialize(): Origin
        {
            if (!isset(self::$instance)) {
                self::$instance = new Origin();
            }
            return self::$instance;
        }
    }

