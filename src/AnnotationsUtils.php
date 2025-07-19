<?php

    namespace Daniel\Origins;

    use ReflectionAttribute;
    use ReflectionClass;
    use ReflectionMethod;
    use ReflectionObject;
    use ReflectionParameter;
    use ReflectionProperty;

    final class AnnotationsUtils
    {

        public static function isAnnotationPresent(ReflectionProperty|ReflectionClass|ReflectionMethod|ReflectionParameter|ReflectionObject $target, string $annotationClassName) : bool{
            return !empty($target->getAttributes($annotationClassName));
        }

        public static function getAnnotationArgs(ReflectionProperty|ReflectionClass|ReflectionMethod|ReflectionParameter|ReflectionObject|ReflectionAttribute $target, string $annotationClassName, bool $associative = false){
            if($target instanceof ReflectionAttribute){
                $args = $target->getArguments();
                return self::normalizeArrayByProps($associative, $args, $target);
            }else{
                $attributes = $target->getAttributes($annotationClassName);
                if (empty($attributes)) {
                    return null;
                }
                $attribute = $attributes[0];
                $args = $attribute->getArguments();
                return self::normalizeArrayByProps($associative, $args, $attribute);
            }
        }

        public static function getAnnotation(ReflectionProperty|ReflectionClass|ReflectionMethod|ReflectionParameter|ReflectionObject $target, string $annotationClassName, bool $getInstance = false){
            $attributes = $target->getAttributes($annotationClassName);
            if (empty($attributes)) {
                return null;
            }
            return $attributes[0];
        }

        public static function getAnnotations(ReflectionProperty|ReflectionClass|ReflectionMethod|ReflectionParameter|ReflectionObject $target, string $annotationClassName): array {
            return $target->getAttributes($annotationClassName);
        }

        public static function getAnnotationInstance(ReflectionProperty|ReflectionClass|ReflectionMethod|ReflectionParameter|ReflectionObject|ReflectionAttribute $target, string $annotationClassName){
            if($target instanceof ReflectionAttribute){
                return $target->newInstance();
            }else{
                $attributes = $target->getAttributes($annotationClassName);
                if (empty($attributes)) {
                    return null;
                }
                $attribute = $attributes[0];
                return $attribute->newInstance();
            }
        }

        public static function getAnnotationInstances(ReflectionProperty|ReflectionClass|ReflectionMethod|ReflectionParameter|ReflectionObject $target, string $annotationClassName): array {
            return array_map(fn($attr) => $attr->newInstance(), $target->getAttributes($annotationClassName));
        }

        private static function normalizeArrayByProps(bool $associative, array &$arr, ReflectionAttribute $attribute): array{
            $isAssociative = array_keys($arr) !== range(0, count($arr) - 1);
            if($associative && !$isAssociative){
                $constructor = (new ReflectionClass($attribute->getName()))->getConstructor();
                if ($constructor !== null) {
                    $params = $constructor->getParameters();
                    $assocArray = [];

                    foreach ($params as $index => $param) {
                        if (array_key_exists($index, $arr)) {
                            $assocArray[$param->getName()] = $arr[$index];
                        }
                    }

                    return $assocArray;
                }
            }
            return array_values($arr);
        }

    }


?>