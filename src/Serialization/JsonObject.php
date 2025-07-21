<?php

    namespace Daniel\Origins\Serialization;

    use Daniel\Origins\Annotations\Serialization\IgnoreNulls;
    use Daniel\Origins\AnnotationsUtils;
    use ReflectionObject;

    final class JsonObject
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
            if (is_string($json)) {
                $json = json_decode($json, true);
            }

            if (!is_array($json)) {
                throw new \InvalidArgumentException("JSON inválido fornecido.");
            }

            if (is_string($target)) {
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
                    if ($type && !$type->isBuiltin() && is_array($value)) {
                        $className = $type->getName();
                        $value = $this->unserialize($value, $className);
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
                $name = $prop->getName();
                $value = $prop->getValue($object);

                if ($ignoreNulls && $value === null) {
                    continue;
                }

                if (is_object($value)) {
                    $value = $this->objectToArray($value);
                }

                if (is_array($value)) {
                    $value = array_map(function ($item) {
                        return is_object($item) ? $this->objectToArray($item) : $item;
                    }, $value);
                }

                $result[$name] = $value;
            }

            return $result;
        }


        public static function defaultSerialization(object $object, int $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) {
            return (new self($flags))->serialize($object, $flags);
        }

        public static function defaultUnserialization(array|string $json, string|object $target){
            return (new self())->unserialize($json, $target);
        }
        
    }
    

?>