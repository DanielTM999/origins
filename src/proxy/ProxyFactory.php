<?php
    namespace Daniel\Origins\proxy;

    use Daniel\Origins\Annotations\DisableProxy;
    use Daniel\Origins\AnnotationsUtils;
    use Daniel\Origins\Aop\Aspect;
    use ReflectionClass;

    final class ProxyFactory{

        private readonly object $target;
        private readonly ObjectInterceptor $interceptor;
        private readonly string $targetClass;
        private readonly ReflectionClass $reflection;

        public function __construct(object $target, ObjectInterceptor $interceptor)
        {
            $this->target = $target;
            $this->interceptor = $interceptor;
            $this->targetClass = get_class($target);
            $this->reflection = new \ReflectionClass($this->targetClass);
        }

        public function createProxy(): object{
            if (!$this->allowProxy()) return $this->target;

            $proxyClassName = '__Proxy_' . str_replace('\\', '_', $this->targetClass) . md5(microtime(true));

            if (!class_exists($proxyClassName)) {
                $code = $this->createExtensiveClass($proxyClassName);
                eval($code);
            }

            $args = $this->getContructorArgs();
            $instance = new $proxyClassName(...$args);
            $this->copyProperties($instance);
            return $instance;
        }

        public function getProxyDefinition(): string{
            $proxyClassName = '__Proxy_' . str_replace('\\', '_', $this->targetClass) . md5(microtime(true));
            return $this->createExtensiveClass($proxyClassName);
        }

        private function createExtensiveClass(string $proxyClassName){
            $escapedBaseClass = '\\' . ltrim($this->targetClass, '\\');
            $contructorCode = $this->createConstructorCode();
            $methodsCode = $this->createMethodsOverrideCode();
            $code = <<<PHP
                class $proxyClassName extends $escapedBaseClass {
                    private \$__target;
                    private \$__interceptor;
                
                    $contructorCode

                    $methodsCode

                    public function __call(\$name, \$args) {
                        if (\$this->__interceptor && method_exists(\$this->__interceptor, 'invoke')) {
                            return \$this->__interceptor->invoke(\$this->__target, \$name, \$args);
                        }
                        return call_user_func_array([\$this->__target, \$name], \$args);
                    }
                }
            PHP;
            return $code;
        }

        private function createConstructorCode(): string {
            $constructor = $this->reflection->getConstructor();

            if (!$constructor || !$constructor->isPublic()) {
            
                return <<<PHP
                public function __construct(\$__target, \$__interceptor) {
                    \$this->__target = \$__target;
                    \$this->__interceptor = \$__interceptor;
                }
                PHP;
            }

            $params = [];
            $args = [];

            $params[] = '$__target';
            $params[] = '$__interceptor';

            foreach ($constructor->getParameters() as $param) {
                $paramCode = '';

                if ($param->hasType()) {
                    $type = $param->getType();
                    if ($type instanceof \ReflectionNamedType) {
                        $paramCode .= ($type->allowsNull() ? '?' : '') . $type->getName() . ' ';
                    } elseif ($type instanceof \ReflectionUnionType || $type instanceof \ReflectionIntersectionType) {
                        $types = $type->getTypes();
                        if (!empty($types) && $types[0] instanceof \ReflectionNamedType) {
                            $paramCode .= ($types[0]->allowsNull() ? '?' : '') . $types[0]->getName() . ' ';
                        }
                    }
                }

                if ($param->isPassedByReference()) {
                    $paramCode .= '&';
                }
                if ($param->isVariadic()) {
                    $paramCode .= '...';
                }

                $paramCode .= '$' . $param->getName();

                if ($param->isOptional() && !$param->isVariadic()) {
                    if ($param->isDefaultValueAvailable()) {
                        $defaultVal = var_export($param->getDefaultValue(), true);
                        $paramCode .= ' = ' . $defaultVal;
                    } else {
                        $paramCode .= ' = null';
                    }
                }

                $params[] = $paramCode;

                $args[] = ($param->isVariadic() ? '...' : '') . '$' . $param->getName();
            }

            $paramsStr = implode(', ', $params);
            $argsStr = implode(', ', $args);

            return <<<PHP
                public function __construct($paramsStr) {
                    parent::__construct($argsStr);
                    \$this->__target = \$__target;
                    \$this->__interceptor = \$__interceptor;
                }
            PHP;
        }

        private function allowProxy(): bool{
            if ($this->reflection->isFinal()) return false;
            if (AnnotationsUtils::isAnnotationPresent($this->reflection, DisableProxy::class)) return false;
            if($this->target instanceof Aspect) return false;
            return true;
        }

        private function createMethodsOverrideCode(): string {
            $methodsCode = '';

            foreach ($this->reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                if ($method->isConstructor() || $method->isStatic()) {
                    continue;
                }

                $name = $method->getName();
                $params = [];
                $args = [];
                $argsRef = [];   
                
                foreach ($method->getParameters() as $param) {
                    $paramCode = '';

                    if ($param->hasType()) {
                        $type = $param->getType();
                        if ($type instanceof \ReflectionNamedType) {
                            $paramCode .= ($type->allowsNull() ? '?' : '') . $type->getName() . ' ';
                        } elseif ($type instanceof \ReflectionUnionType || $type instanceof \ReflectionIntersectionType) {
                            $types = $type->getTypes();
                            if (!empty($types) && $types[0] instanceof \ReflectionNamedType) {
                                $paramCode .= ($types[0]->allowsNull() ? '?' : '') . $types[0]->getName() . ' ';
                            }
                        }
                    }

                    if ($param->isPassedByReference()) {
                        $paramCode .= '&';
                    }

                    if ($param->isVariadic()) {
                        $paramCode .= '...';
                    }

                    $paramName = '$' . $param->getName();
                    $paramCode .= $paramName;

                    if ($param->isOptional() && !$param->isVariadic()) {
                        $defaultValue = $param->isDefaultValueAvailable() ? var_export($param->getDefaultValue(), true) : 'null';
                        $paramCode .= ' = ' . $defaultValue;
                    }

                    $params[] = $paramCode;

                    if ($param->isVariadic()) {
                        $args[] = '...' . $paramName;
                        $argsRef[] = '...' . $paramName;
                    } else {
                        $args[] = $paramName;
                        $argsRef[] = ($param->isPassedByReference() ? '&' : '') . $paramName;
                    }
                }

                $paramsStr = implode(', ', $params);
                $argsStr = implode(', ', $args);
                $argsRefStr = 'array(' . implode(', ', $argsRef) . ')';

                $returnTypeCode = '';
                $returnType = $method->getReturnType();
                if ($returnType) {
                    if ($returnType instanceof \ReflectionNamedType) {
                        $returnTypeCode = ': ' . ($returnType->allowsNull() ? '?' : '') . $returnType->getName();
                    } elseif ($returnType instanceof \ReflectionUnionType || $returnType instanceof \ReflectionIntersectionType) {
                        $types = $returnType->getTypes();
                        if (!empty($types) && $types[0] instanceof \ReflectionNamedType) {
                            $returnTypeCode = ': ' . ($types[0]->allowsNull() ? '?' : '') . $types[0]->getName();
                        }
                    }
                }

                $isVoid = $returnType instanceof \ReflectionNamedType && $returnType->getName() === 'void';

                if ($isVoid) {
                    $methodsCode .= <<<PHP
                        public function $name($paramsStr)$returnTypeCode {
                            if (\$this->__interceptor && method_exists(\$this->__interceptor, 'invoke')) {
                                \$args = $argsRefStr;
                                \$this->__interceptor->invoke(\$this->__target, '$name', \$args);
                            } else {
                                \$this->__target->$name($argsStr);
                            }
                        }

                    PHP;
                } else {
                    $methodsCode .= <<<PHP
                        public function $name($paramsStr)$returnTypeCode {
                            if (\$this->__interceptor && method_exists(\$this->__interceptor, 'invoke')) {
                                \$args = $argsRefStr;
                                return \$this->__interceptor->invoke(\$this->__target, '$name', \$args);
                            }
                            return \$this->__target->$name($argsStr);
                        }

                    PHP;
                }
            }

            return $methodsCode;
        }

        private function getContructorArgs(): array{
            $args = [$this->target, $this->interceptor];
            $constructor = $this->reflection->getConstructor();

            if (!$constructor) {
                return $args; 
            }

            foreach ($constructor->getParameters() as $param) {
                $name = $param->getName();

                if ($this->reflection->hasProperty($name)) {
                    $prop = $this->reflection->getProperty($name);
                    $prop->setAccessible(true);
                    $args[] = $prop->getValue($this->target);
                } else {
                    if ($param->isDefaultValueAvailable()) {
                        $args[] = $param->getDefaultValue();
                    } else {
                        $args[] = null;
                    }
                }
            }

            return $args;
        }

        function copyProperties(object &$source): void {
            foreach ($this->reflection->getProperties() as $property) {
                $property->setAccessible(true);
                $value = $property->getValue($this->target);
                $property->setValue($source, $value);
            }

        }
        
    }

?>