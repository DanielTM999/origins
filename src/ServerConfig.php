<?php

    namespace Daniel\Origins;

use Daniel\Origins\Annotations\FilterPriority;
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
                $priorityAArgs = AnnotationsUtils::getAnnotationArgs($a, FilterPriority::class) ?? [0];
                $priorityBArgs = AnnotationsUtils::getAnnotationArgs($b, FilterPriority::class) ?? [0];
                $priorityA = $priorityAArgs[0] ?? 0;
                $priorityB = $priorityBArgs[0] ?? 0;
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