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
            $vars = $reflect->getProperties();

            if ($constructor !== null){
                $parameters = $constructor->getParameters();
                if(empty($parameters)){
                    return $this->injectNoContructors($reflect, $vars, $Dmanager);
                }
            }else{
                return $this->injectNoContructors($reflect, $vars, $Dmanager);
            }
        }

        private function injectNoContructors(ReflectionClass $reflect, $vars, DependencyManager $Dmanager)
        {
            $instance = $reflect->newInstance();
            if (count($vars) > 0) {
                foreach ($vars as $prop) {
                    if ($this->isAnnotetionPresent($prop, Inject::class)) {
                        $propClass = $prop->getType();
                        $args = $this->getAnnotetion($prop, Inject::class);
                        if (isset($propClass)) {
                            $object = $Dmanager->get($propClass);
                            $prop->setAccessible(true);
                            $prop->setValue($instance, $object);
                        }else if(!empty($args)){
                            $object = $Dmanager->get($args[0]);
                            $prop->setAccessible(true);
                            $prop->setValue($instance, $object);
                        }else {
                            echo "Não posso injetar algo na variavel [ {$prop->getName()} ], essa variavel tem que possuir um tipo";
                            die();
                        }
                    }
                }
            }
            return $instance;
        }

        private function isAnnotetionPresent(ReflectionProperty $prop, string $atribute): bool
        {
            $attributes  = $prop->getAttributes();
            foreach ($attributes as $attribute) {
                if ($attribute->getName() === $atribute) {
                    return true;
                }
            }
            return false;
        }

        private function getAnnotetion(ReflectionProperty $prop, string $atribute)
        {
            $attributes  = $prop->getAttributes();
            foreach ($attributes as $attribute) {
                if ($attribute->getName() === $atribute) {
                    return $arguments = $attribute->getArguments();
                }
            }
            return null;
        }
    }

?>