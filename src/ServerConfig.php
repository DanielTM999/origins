<?php

    namespace Daniel\Origins;
    use Override;
    use ReflectionClass;
    use ReflectionProperty;

    class ServerConfig extends Config{
        private DependencyManager $di;
        private static $config = [];

        public function __construct(DependencyManager $di)
        {
            $this->di = $di;
        }

        #[Override]
        public function ConfigOnInit() : void{


            if(isset($_SESSION["origins.initializers"])){
                $classes = $_SESSION["origins.initializers"] ?? [];
                foreach ($classes as $class){
                    self::$config[] = new ReflectionClass($class);
                }
            }else{
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
            }

            

            usort(self::$config, function($a, $b){
                $attributesA = $a->getAttributes(FilterPriority::class);
                $attributesB = $b->getAttributes(FilterPriority::class);

                $priorityAArgs0 = isset($attributesA[0]) ? $attributesA[0]->getArguments() : [0];
                $priorityBArgs0 = isset($attributesB[0]) ? $attributesB[0]->getArguments() : [0];
                $priorityA = isset($priorityAArgs0[0]) ? $priorityAArgs0[0] : 0;
                $priorityB = isset($priorityBArgs0[0]) ? $priorityBArgs0[0] : 0;

                return $priorityB <=> $priorityA;
            });
            
            $this->configure();

        }


        private function configure(){
            foreach(self::$config as $d){
                $obj = $this->getInstanceBy($d, $this->di);
                $obj->ConfigOnInit();
            }
        }

        private function getInstanceBy(ReflectionClass $reflect, DependencyManager $Dmanager){
            $constructor = $reflect->getConstructor();
            
            if ($constructor !== null){
                $parameters = $constructor->getParameters();
                if(empty($parameters)){
                    return $Dmanager->tryCreate($reflect);
                }
            }else{
                return $Dmanager->tryCreate($reflect);
            }
        }

    }

?>