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

        public static function getAnnotationArgs(ReflectionProperty|ReflectionClass|ReflectionMethod|ReflectionParameter|ReflectionObject|ReflectionAttribute $target, string $annotationClassName, bool $getInstance = false){
            if($target instanceof ReflectionAttribute){
                return $getInstance ? $target->newInstance() : $target->getArguments();
            }else{
                $attributes = $target->getAttributes($annotationClassName);
                if (empty($attributes)) {
                    return null;
                }
                $attribute = $attributes[0];
                return $getInstance ? $attribute->newInstance() : $attribute->getArguments();
            }
        }

        public static function getAnnotation(ReflectionProperty|ReflectionClass|ReflectionMethod|ReflectionParameter|ReflectionObject $target, string $annotationClassName, bool $getInstance = false){
            $attributes = $target->getAttributes($annotationClassName);
            if (empty($attributes)) {
                return null;
            }
            return $attributes[0];
        }

    }


?>