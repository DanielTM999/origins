<?php

    namespace Daniel\Origins;
    use Override;
    use ReflectionClass;

    class ServerConfig extends Config{
        private DependencyManager $di;
        private static $config = [];

        public function __construct(DependencyManager $di)
        {
            $this->di = $di;
        }

        #[Override]
        public function ConfigOnInit() : void{
            $classes = get_declared_classes();
            foreach ($classes as $class){
                $reflect = new ReflectionClass($class);
                $parentClass = $reflect->getParentClass();
                if ($parentClass !== false) {
                    $parentClassName = $parentClass->getName();
                    if($parentClassName === OnInit::class){
                        self::$config[] = $reflect;
                    }
                }
            }
            
            $this->configure();

        }


        private function configure(){
            foreach(self::$config as $d){
                $nome = $d->getName();
                $obj = $this->di->get($nome);
                $obj->ConfigOnInit();
            }
        }
    }

?>