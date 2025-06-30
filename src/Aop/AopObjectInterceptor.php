<?php
    namespace Daniel\Origins\Aop;

    use Daniel\Origins\DependencyManager;
use Daniel\Origins\FilterPriority;
use Daniel\Origins\Log;
use Daniel\Origins\proxy\ObjectInterceptor;
    use Override;
use ReflectionClass;
use ReflectionObject;

    final class AopObjectInterceptor extends ObjectInterceptor
    {
        private static bool $load = false;
        private static bool $loadInstances = false;
        private static array $aspectsClass = [];
        private static array $aspectsInstances = [];
        private readonly DependencyManager $dManager;

        public function __construct(DependencyManager $dManager) {
            $this->dManager = $dManager;
        }

        #[Override]
        public function invoke(object $target, string $method, array $args){
            self::fillAspects();
            self::createAspectsInstances($this->dManager);
            $result = null;
            $executedAspects = [];
            foreach (self::$aspectsInstances as $index => $aspect) {
                if (self::canExecuteAspects($aspect, $target, $method, $args)) {
                    self::executeAspect("aspectBefore", $aspect, $target, $method, $args, $result);
                    $executedAspects[] = $index;
                }
            }

            $result = $target->$method(...$args);

            foreach ($executedAspects as $index) {
                $aspect = self::$aspectsInstances[$index];
                $result = self::executeAspect("aspectAfter", $aspect, $target, $method, $args, $result);
            }
            
            return $result;
        }
        
        private static function fillAspects(){
            if(!self::$load){
                $aspects = self::getAspects();
                foreach($aspects as $aspect){
                    $reflect = new ReflectionClass($aspect);
                    self::$aspectsClass[] = $reflect;
                }
                usort(self::$aspectsClass, function($a, $b){
                    $attributesA = $a->getAttributes(FilterPriority::class);
                    $attributesB = $b->getAttributes(FilterPriority::class);

                    $priorityAArgs0 = isset($attributesA[0]) ? $attributesA[0]->getArguments() : [0];
                    $priorityBArgs0 = isset($attributesB[0]) ? $attributesB[0]->getArguments() : [0];
                    $priorityA = isset($priorityAArgs0[0]) ? $priorityAArgs0[0] : 0;
                    $priorityB = isset($priorityBArgs0[0]) ? $priorityBArgs0[0] : 0;

                    return $priorityB <=> $priorityA;
                });
                self::$load = true;
            }
        }

        private static function getAspects(): array{
            unset($_SESSION["origins.loaders"]);
            if(isset($_SESSION["origins.loaders"])){
                $loaders = $_SESSION["origins.loaders"]; 
                return $loaders["aspects"] ?? [];
            }else{
                $aspects = [];
                $classes = get_declared_classes();
                foreach ($classes as $class){
                    $reflect = new ReflectionClass($class);
                    $parentClass = $reflect->getParentClass();
                    if ($parentClass !== false) {
                        $parentClassName = $parentClass->getName();
                        if($parentClassName === Aspect::class){
                            $aspects[] = $class;
                        }
                    }
                }
                return $aspects;
            }

        }

        private static function createAspectsInstances(DependencyManager $dManager){
            if(!self::$loadInstances){
                foreach(self::$aspectsClass as $aspect){
                    $aspectName = $aspect->getName();
                    $aspectInstence = null;
                    if(isset(self::$aspectsInstances[$aspectName])){
                        $aspectInstence = self::$aspectsInstances[$aspectName];
                    }else{
                        $aspectInstence = self::createAspectsInstance($aspect, $dManager);
                        if(isset($aspectInstence)){
                            self::$aspectsInstances[$aspectName] = $aspectInstence;
                        }
                    }  
                }
                self::$loadInstances = true;
            }
        }
        
        private static function createAspectsInstance(ReflectionClass $reflect, DependencyManager $dManager): object{
            return $dManager->tryCreate($reflect);
        }

        private static function canExecuteAspects(Aspect &$aspect, object &$target, string &$method, array &$args){
            $ReflectObject = new ReflectionObject($target);
            if (!$ReflectObject->hasMethod($method)) {
                return false;
            }
            $reflectionMethod = $ReflectObject->getMethod($method);
            return $aspect->pointCut($target, $reflectionMethod, $args);
        }

        private static function executeAspect(string $point, Aspect &$aspect, object &$target, string &$method, array &$args, object|null &$result){
            $ReflectObject = new ReflectionObject($target);
            if (!$ReflectObject->hasMethod($method)) {
                return;
            }

            $reflectionMethod = $ReflectObject->getMethod($method);

            if($point === "aspectBefore"){
                $aspect->aspectBefore($target, $reflectionMethod, $args);
            }else if($point === "aspectAfter"){
                return $aspect->aspectAfter($target, $reflectionMethod, $args, $result);
            }

        }
        
    }
?>