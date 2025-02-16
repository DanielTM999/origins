<?php
    namespace Daniel\Origins;

use ReflectionAttribute;
use ReflectionClass;
    use ReflectionProperty;

    class DependencyManager{ 
        private static $dependency_creator = [];
        private static $dependencys = [];

        public function __construct()
        {
            $this->autoInject();
        } 

        public function start() : void{

            if(isset($_SESSION["origins.dependencys"])){
                $classes = $_SESSION["origins.dependencys"];
                foreach($classes as $c){
                    self::$dependency_creator[] = new ReflectionClass($c);
                }
            }else{
                $classes = get_declared_classes();
                foreach($classes as $c){
                    $reflect = new ReflectionClass($c);
                    $atrbute = $reflect->getAttributes(Dependency::class);
                    if(!empty($atrbute)){
                        if(!$reflect->isInterface()){
                            self::$dependency_creator[] = $reflect;
                        }
                    }
    
                    $parentClass = $reflect->getParentClass();
                    if ($parentClass !== false) {
                        $parentClassName = $parentClass->getName();
                        if($parentClassName === OnInit::class){
                            self::$dependency_creator[] = $reflect;
                        }
                    }
                }
            }
            $this->create();

        }

        public function addDependency(string $dependency, object $object){
            $notDepend = true;
            if(isset(self::$dependencys[$dependency])){
                self::$dependencys[$dependency] = $object;
               $notDepend = false;
            }

            if($notDepend){
                self::$dependencys[$dependency] = $object;
            }
            
        }

        public function get(string $dependency){
            $object = null;
            if(isset(self::$dependencys[$dependency])){
                if(is_callable(self::$dependencys[$dependency])){
                    $object = self::$dependencys[$dependency]();
                }else if(is_object(self::$dependencys[$dependency])){
                    $object = self::$dependencys[$dependency];
                }
            }
            return $object;
        }

        public function showDependencys(){
            foreach(self::$dependencys as $d){
                var_dump($d);
                echo "<br>";
                echo "<br>";
            }
        }

        private function create(){
            foreach(self::$dependency_creator as $d){
                $name = $d->getName();
                $instance = $this->getInstanceOrActivator($d);
                $interfaces = $d->getInterfaces();
                foreach ($interfaces as $interface) {
                    $interfaceName = $interface->getName();
                    self::$dependencys[$interfaceName] = $instance;
                }

                $parentClass = $d->getParentClass();
                if ($parentClass !== false) {
                    $parentClassName = $parentClass->getName();
                    self::$dependencys[$parentClassName] = $instance;
                }

                self::$dependencys[$name] = $instance;   
            }
        } 

        private function getInstanceOrActivator(ReflectionClass $reflect){
            $vars = $reflect->getProperties();
            $constructor = $reflect->getConstructor();

            
            if ($constructor !== null){

            }else{
                $atrbuteData = $reflect->getAttributes(Dependency::class);
                $singleton = $this->isSingleton($atrbuteData);
                if($singleton){
                    return $this->getIntanceInternal($reflect, $vars);
                }else{
                    $activator = function() use ($reflect, $vars){
                        return $this->getIntanceInternal($reflect, $vars);
                    };
                    return $activator;
                }

            }
        }

        private function isSingleton(array $atribute) : bool {
            if (!empty($atribute)){
                $dependencyAttribute = $atribute[0];
                $arguments = $dependencyAttribute->getArguments();
                if(isset($arguments[0])){
                    return $arguments[0];
                }
                return false;
            }

            return false;
        }

        private function getDependency(ReflectionProperty $var){
            $name = $var->getType();
            $name = $name->getName();

            if(isset(self::$dependencys[$name])){
                if(is_callable(self::$dependencys[$name])){
                    return self::$dependencys[$name]();
                }else if(is_object(self::$dependencys[$name])){
                    return self::$dependencys[$name];
                }else{
                    return null;
                }
            }else{
                $dependencyname = null;
                if ($this->isAnnotetionPresent($var, Inject::class)){
                    $propClass = $var->getType();
                    $args = $this->getAnnotetion($var, Inject::class);
                    if (isset($propClass)) {
                        $dependencyname = $propClass->getName();
                    }else if(!empty($args)){
                        $dependencyname = $args[0];
                    }
                }

                if(isset($dependencyname)){
                    $reflectBase = new ReflectionClass($dependencyname);
                    $reflect = $reflectBase;
                    if($reflectBase->isInterface()){
                        foreach(self::$dependency_creator as $service){
                            if ($service->implementsInterface($reflectBase->getName())) {
                                $reflect = $service;
                                break;
                            }
                        }
                    }

                    $object = $this->getInstanceOrActivator($reflect);
                    if($dependencyname === DependencyManager::class){
                        return $this;
                    }else if(is_callable($object)){
                        return $object();
                    }else if(is_object($object)){
                        return $object;
                    }else{
                        return null;
                    }
                }
                return null;
            }
        }

        private function autoInject(): void{
            if(!isset(self::$dependencys[DependencyManager::class])){
                self::$dependencys[DependencyManager::class] = $this;
            }
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


        private function getIntanceInternal(ReflectionClass $reflect, $vars){
            $instance = $reflect->newInstanceWithoutConstructor();
            foreach($vars as $var){
                $type = $var->getType() ?? "";
                if(isset($type)){
                    $name = $type->getName() ?? "";
    
                    if($name === "PDO"){
                        $var->setAccessible(false);
                    }else{
                        if ($this->isAnnotetionPresent($var, Inject::class)){
                            $object = $this->getDependency($var);
                            $var->setAccessible(true);
                            $var->setValue($instance, $object);
                        }
                    }
                
                }
            }
            return $instance;
        }
    }
?>