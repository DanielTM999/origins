<?php

    namespace Daniel\Origins;

    use ReflectionClass;
    use ReflectionMethod;
    use ReflectionProperty;

    final class AnnotationsUtils
    {

        public static function isAnnotationPresent(ReflectionProperty|ReflectionClass|ReflectionMethod $target, string $annotationClassName) : bool{
            return !empty($target->getAttributes($annotationClassName));
        }

        public static function getAnnotation(ReflectionProperty|ReflectionClass|ReflectionMethod $target, string $annotationClassName, bool $getInstance = false){
            $attributes = $target->getAttributes($annotationClassName);
            if (empty($attributes)) {
                return null;
            }
            $attribute = $attributes[0];
            return $getInstance ? $attribute->newInstance() : $attribute->getArguments();
        }

    }


?>