<?php

    namespace Daniel\Origins\Serialization;

    use Daniel\Origins\Annotations\Serialization\IgnoreNulls;
    use Daniel\Origins\Annotations\Serialization\IgnoreProperty;
    use Daniel\Origins\Annotations\Serialization\ListOf;
    use Daniel\Origins\Annotations\Serialization\SerializationName;
    use Daniel\Origins\Annotations\Serialization\Trim;
    use Daniel\Origins\AnnotationsUtils;
    use ReflectionObject;

    class JsonObject
    {

        private readonly int $flags;

        public function __construct(int $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) {
            $this->flags = $flags;
        }

        public function serialize(object $object) {
            $array = $this->objectToArray($object);
            return json_encode($array, $this->flags);
        }

        public function unserialize(array|string $json, string|object $target){
            $isSimpleArray = false;
            if (is_string($json)) {
                $temp = $json; 
                $json = json_decode($json, true);

                if ($json === null && json_last_error() !== JSON_ERROR_NONE) {
                    $json = $temp;
                    $isSimpleArray = true;
                }
            }
             
            if (!is_array($json) && !$isSimpleArray) {
                throw new \InvalidArgumentException("JSON inválido fornecido.");
            }

            if (is_string($target)) {
                if ($this->isBaseType($target)) {
                    return $this->handleBaseType($target, $json);
                }

                if ($this->isSimpleConstructableClass($target)) {
                    return $this->handleSimpleConstructableClass($target, $json);
                }

                if (!class_exists($target)) {
                    throw new \InvalidArgumentException("Classe '$target' não encontrada.");
                }

                $object = new $target();
            } else {
                $object = $target;
            }

            $ref = new \ReflectionObject($object);
            foreach ($ref->getProperties() as $prop) {
                $name = $prop->getName();

                if (array_key_exists($name, $json)) {
                    $prop->setAccessible(true);
                    $value = $json[$name];

                    $type = $prop->getType();

                    if ($type instanceof \ReflectionNamedType) {
                        $typeName = $type->getName();

                        if ($type->isBuiltin()){
                            if ($value === '') {
                                $value = null;
                            } elseif ($typeName === 'int') {
                                $value = is_numeric($value) ? (int) $value : null;
                            } elseif ($typeName === 'float') {
                                $value = is_numeric($value) ? (float) $value : null;
                            } elseif ($typeName === 'bool') {
                                $value = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                            } elseif ($typeName === 'string') {
                                if (AnnotationsUtils::isAnnotationPresent($prop, Trim::class)){
                                    $value = trim($value);
                                }
                            } elseif ($typeName === 'array' && is_array($value)) {
                                $serialize = AnnotationsUtils::isAnnotationPresent($prop, ListOf::class);
                                if($serialize){
                                    $listClassType = AnnotationsUtils::getAnnotationArgs($prop, ListOf::class)[0] ?? [];
                                
                                    if (empty($listClassType)) {
                                        throw new \InvalidArgumentException("A anotação #[ListOf] na propriedade '{$prop->getName()}' precisa informar a classe.");
                                    }
                                    $value = array_map(fn($v) => $this->unserialize($v, $listClassType), $value);
                                }
                            }
                        }else{
                            if(is_array($value) && class_exists($typeName)) {
                                $value = $this->unserialize($value, $typeName);
                            }
                        }
                    }

                    $prop->setValue($object, $value);
                }
            }

            return $object;

        }

        private function objectToArray(object $object): array{
            $ref = new ReflectionObject($object);
            $props = $ref->getProperties();
            $result = [];
            $ignoreNulls = AnnotationsUtils::isAnnotationPresent($ref, IgnoreNulls::class);

            foreach ($props as $prop) {
                $prop->setAccessible(true);

                if(AnnotationsUtils::isAnnotationPresent($prop, IgnoreProperty::class)) continue;

                if (!$prop->isInitialized($object)) {
                    if ($ignoreNulls) {
                        continue;
                    }
                    $value = null;
                } else {
                    $value = $prop->getValue($object);
                }

                if ($ignoreNulls && $value === null) {
                    continue;
                }

                if (is_object($value)) {
                    $value = $this->objectToArray($value);
                    if (empty($value)) {
                        $value = new \stdClass();
                    }
                }else if (is_array($value)) {
                    $value = array_map(function ($item) {
                        return is_object($item) ? $this->objectToArray($item) : $item;
                    }, $value);
                }

                $namePropSerializationAtrubuteArray = AnnotationsUtils::getAnnotationArgs($prop, SerializationName::class) ?? [];
                $varName = $prop->getName();
                $name = $namePropSerializationAtrubuteArray[0] ?? $varName;

                $result[$name] = $value;
            }

            return $result;
        }

        
        private function isBaseType(string $type): bool {
            $baseTypes = ['string', 'int', 'float', 'bool'];
            return in_array($type, $baseTypes, true);
        }

        private function handleBaseType(string $type, mixed $json): mixed {
            return match ($type) {
                'string' => (string) $json,
                'int' => is_numeric($json) ? (int) $json : null,
                'float' => is_numeric($json) ? (float) $json : null,
                'bool' => filter_var($json, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
                default => null,
            };
        }

        private function isSimpleConstructableClass(string $className): bool {
            $simpleConstructableClasses = [
                \DateTime::class,
                \DateTimeImmutable::class,
                \SimpleXMLElement::class,
            ];
            return in_array($className, $simpleConstructableClasses, true);
        }

        private function handleSimpleConstructableClass(string $className, mixed $json): object {
            return new $className($json);
        }

        public static function defaultSerialization(object $object, int $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) {
            return (new self($flags))->serialize($object, $flags);
        }

        public static function defaultUnserialization(array|string $json, string|object $target){
            return (new self())->unserialize($json, $target);
        }
        
    }
    

?>