<?php

    namespace Daniel\Origins\Serialization;

    use ReflectionObject;

    final class JsonObject
    {

        public function serialize(object $object, int $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT){
            $array = $this->objectToArray($object);
            return json_encode($array, $flags);
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

            foreach ($props as $prop) {
                $prop->setAccessible(true);
                $name = $prop->getName();
                $value = $prop->getValue($object);

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
        
    }
    

?>