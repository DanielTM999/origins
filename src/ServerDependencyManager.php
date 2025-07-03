<?php

    namespace Daniel\Origins;

    use Daniel\Origins\Annotations\Dependency;
    use Daniel\Origins\Annotations\Inject;
    use Daniel\Origins\Annotations\Qualifier;
    use Daniel\Origins\Annotations\Singleton;
    use Daniel\Origins\Aop\AopObjectInterceptor;
    use Daniel\Origins\proxy\ObjectInterceptor;
    use Daniel\Origins\proxy\ProxyFactory;
    use Exception;
    use Override;
    use ReflectionClass;
    use ReflectionMethod;
    use ReflectionParameter;
    use ReflectionProperty;

    final class ServerDependencyManager implements DependencyManager
    {
        private static $dependency_creator = [];
        private static $dependencies = [];
        private readonly ObjectInterceptor $inteceptor;
    
        public function __construct()
        {
            $this->autoInject();
            $this->inteceptor = new AopObjectInterceptor($this);
        }

        #[Override]
        public function load(): void{
            if(isset($_SESSION["origins.dependencys"])){
                $this->loadBySession();
            }else{
                $this->loadByRuntime();
            }
            $this->create();
        }

        #[Override]
        public function addDependency(string $dependency, object &$object, string $qualifier = "default"): void{
            self::$dependencies[$dependency][$qualifier] = $object;
        }
        
        #[Override]
        public function getDependency(string $dependency, string $qualifier = "default"): object|null{
            $instance = self::$dependencies[$dependency] ?? null;

            if($instance != null){
                $dependecyObject = $instance[$qualifier];
                return is_callable($dependecyObject)
                    ? $dependecyObject()
                    :(is_object($dependecyObject)
                        ? $dependecyObject
                        : null
                    );

            }
        
            return $instance;
        }

        #[Override]
        public function tryCreate(string|ReflectionClass $class): object|null{
            $reflection = null;

            if($class instanceof ReflectionClass){
                $reflection = $class;
            }else{
                $reflection = new ReflectionClass($class);
            }

            $dependecyObject = $this->getInstanceOrActivator($reflection);
        
            return is_callable($dependecyObject)
                    ? $dependecyObject()
                    :(is_object($dependecyObject)
                        ? $dependecyObject
                        : null
                    );
        }

        #[Override]
        public function showDependencys(): string{
            foreach(self::$dependencies as $d){
                var_dump($d);
                echo "<br>";
                echo "<br>";
            }
            return "";
        }

        private function loadBySession(){
            $classes = $_SESSION["origins.dependencys"];
            foreach($classes as $c){
                self::$dependency_creator[] = new ReflectionClass($c);
            }
        }

        private function loadByRuntime(){
            $classes = get_declared_classes();
            foreach($classes as $c){
                $reflect = new ReflectionClass($c);
                if(AnnotationsUtils::isAnnotationPresent($reflect, Dependency::class)){
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

        private function create(){
            foreach(self::$dependency_creator as $dependecy){
                $name = $dependecy->getName();
                $qualifier = $this->getQualifier($dependecy);
                $instance = $this->getInstanceOrActivator($dependecy);

                $interfaces = $dependecy->getInterfaces();
                foreach ($interfaces as $interface) {
                    $interfaceName = $interface->getName();
                    self::$dependencies[$interfaceName][$qualifier] = $instance;
                }

                $parentClass = $dependecy->getParentClass();
                if ($parentClass !== false) {
                    $parentClassName = $parentClass->getName();
                    self::$dependencies[$parentClassName][$qualifier] = $instance;
                }

                self::$dependencies[$name][$qualifier] = $instance;  
            }
        }

        private function getInstanceOrActivator(ReflectionClass $reflect): object|null{
            $constructor = $reflect->getConstructor();
            $isSingleton = AnnotationsUtils::isAnnotationPresent($reflect, Singleton::class);
            $returnedObject = null;
            if ($constructor !== null && $constructor->getNumberOfRequiredParameters() > 0){
                if($isSingleton){
                    $instance = $this->createObjectContructors($reflect, $constructor);
                    $this->fillObject($reflect, $instance);
                    $returnedObject = $this->createProxy($instance);
                }
                $returnedObject = function() use($reflect, $constructor){
                    $instance = $this->createObjectContructors($reflect, $constructor);
                    $this->fillObject($reflect, $instance);
                    return $this->createProxy($instance);
                };
            }else{
                if($isSingleton){
                    $instance = $this->createObjectNoContructors($reflect);
                    $this->fillObject($reflect, $instance);
                    $returnedObject = $this->createProxy($instance);
                }

                $returnedObject = function() use($reflect){
                    $instance = $this->createObjectNoContructors($reflect);
                    $this->fillObject($reflect, $instance);
                    return $this->createProxy($instance);
                };
            }

            return $returnedObject;
        }

        private function fillObject(ReflectionClass|null &$reflect = null, object &$instance): void{
            if($reflect == null) $reflect = new ReflectionClass($instance);

            $vars = $reflect->getProperties();
            foreach($vars as $var){

                $type = $var->getType();
                $name = null;
                if ($type instanceof \ReflectionNamedType) {
                    $name = $type->getName();
                } elseif ($type instanceof \ReflectionUnionType || $type instanceof \ReflectionIntersectionType) {
                    $types = $type->getTypes();
                    if (!empty($types) && $types[0] instanceof \ReflectionNamedType) {
                        $name = $types[0]->getName();
                    }
                }

                if ($name === 'PDO') {
                    $var->setAccessible(false);
                    continue;
                }

                if(AnnotationsUtils::isAnnotationPresent($var, Inject::class)){
                    $object = $this->getInternalDependency($var);
                    $var->setAccessible(true);
                    $var->setValue($instance, $object);
                }
            }
        }

        private function createObjectNoContructors(ReflectionClass &$reflect): object|null{
            return $reflect->newInstance();
        }

        private function createObjectContructors(ReflectionClass &$reflect, ReflectionMethod $constructor): object|null{
            $params = $constructor->getParameters();
            $args = [];

            foreach ($params as $param) {
                if ($param->isDefaultValueAvailable()) {
                    $args[] = $param->getDefaultValue();
                    continue;
                }

                $type = $param->getType();

                if ($type instanceof \ReflectionNamedType) {
                    if (!$type->isBuiltin()) {
                        try{
                            $args[] = $this->getInternalDependency($param);
                        }catch(\Exception $e){
                            $args[] = null;
                        }
                    } else {
                       return $this->getDefaultValueFor($type);
                    } 
                } else {
                    $args[] = null;
                }
            }

            return $reflect->newInstanceArgs($args);
        }

        private function getInternalDependency(ReflectionProperty|ReflectionParameter $var): object|null{
            $varName = $var->getName();
            $name = $var->getType();
            $name = $name->getName();
            $qualifier = $this->getQualifier($var);
            $dependecyPresent = isset(self::$dependencies[$name]);
            
            if($dependecyPresent){
                $dependecyObject = self::$dependencies[$name][$qualifier];
                return is_callable($dependecyObject)
                    ? $dependecyObject()
                    : (is_object($dependecyObject)
                        ? $dependecyObject
                        : throw new Exception("no qualifier defined to: $name|$varName"));
            }

            if ($var instanceof \ReflectionProperty) {
                
                $subDependencyName = null;
                if (AnnotationsUtils::isAnnotationPresent($var, Inject::class)){
                    $propClass = $var->getType();
                    $args = AnnotationsUtils::getAnnotationArgs($var, Inject::class);

                    if (isset($propClass)) {
                        $subDependencyName = $propClass->getName();
                    }else if(!empty($args)){
                        $subDependencyName = $args[0];
                    }
                }

                return $this->injectSubDependecies($subDependencyName);
                
            }else if($var instanceof \ReflectionParameter){
                $subDependencyName = null;
                $propClass = $var->getType();
                if ($propClass instanceof \ReflectionNamedType) {
                    $typeName = $propClass->getName();

                    if (!$propClass->isBuiltin() && $typeName !== 'mixed') {
                        $subDependencyName = $typeName;
                        return $this->injectSubDependecies($subDependencyName);
                    }
                    return $this->getDefaultValueFor($propClass);
                }

            }

            return null;
        }

        private function getQualifier(ReflectionProperty|ReflectionClass|ReflectionParameter $reflect): string{

            if(AnnotationsUtils::isAnnotationPresent($reflect, Qualifier::class)){
                $args = AnnotationsUtils::getAnnotationArgs($reflect, Qualifier::class);
                return (!empty($args) && isset($args['qualifier'])) ? $args['qualifier'] : 'default';
            }

            return "default";
        }

        private function injectSubDependecies(string $className): object|null{

            if(isset($className)){
                $reflectBase = new ReflectionClass($className);
                $reflect = $reflectBase;
                if($reflectBase->isInterface()){
                    foreach(self::$dependency_creator as $service){
                        if ($service->implementsInterface($reflectBase->getName())) {
                            $reflect = $service;
                            break;
                        }
                    }
                }

                $dependecyObject = $this->getInstanceOrActivator($reflect);
                return is_callable($dependecyObject)
                ? $dependecyObject()
                : (is_object($dependecyObject)
                    ? $dependecyObject
                    : null);
            }

            return null;
        }

        private function autoInject(): void{
            $qualifier = "default";
            if(!isset(self::$dependencies[DependencyManager::class])){
                self::$dependencies[DependencyManager::class][$qualifier] = $this;
            }
        }

        private function createProxy(object $realInstance): object{
            $proxyFactory = new ProxyFactory($realInstance, $this->inteceptor);
            return $proxyFactory->createProxy();
        }

        private function getDefaultValueFor(?\ReflectionNamedType $type): mixed
        {
            if (!$type) return null;

            return match ($type->getName()) {
                'int'    => 0,
                'float'  => 0.0,
                'bool'   => false,
                'string' => '',
                'array'  => [],
                default  => null,
            };
        }


    }
   

?>