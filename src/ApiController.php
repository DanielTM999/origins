<?php
    namespace DanielTm\Origins;
    use ReflectionClass;

    class ApiController{

        protected function Render($path){
            if (file_exists($path)) {
                include $path;
            } else {
                echo "Arquivo HTML não encontrado: $path";
            }
        }

        protected function sendJson($json){
            echo json_encode($json);
        }

        protected function send($value){
            echo $value;
        }

        protected function sendObject(object $entity, bool $base = false){
            $seriazivable = new JsonSerializable($entity);
            echo $seriazivable->Get();
        }

    }

    class JsonSerializable{
        private object $object;
        private ReflectionClass $reflect;
        private $preSerializable = [];

        public function __construct(object $object)
        {
            $this->object = $object;
            $this->reflect = new ReflectionClass($this->object);
            $this->preSerializable();
        }

        public function Getbase(){
            return $this->preSerializable;
        }

        public function Get(){
            return json_encode($this->preSerializable);
        }

        private function preSerializable(){
            $vars = $this->reflect->getProperties();

            foreach($vars as $var){
                $value = $var->getValue($this->object);

                if(is_object($value)){
                    $this->preSerializable[$var->getName()] = $this->subSerializable($value);
                }else{
                    $this->preSerializable[$var->getName()] = $value;
                }
            }
        }

        private function subSerializable(object $object){
            $reflecton = new ReflectionClass($object);
            $vars = $reflecton->getProperties();
            $Seriazable = [];
            foreach($vars as $var){
                $value = $var->getValue($object);
                if(is_object($value)){
                    $Seriazable[$var->getName()] = $this->subSerializable($value);
                }else{
                    $Seriazable[$var->getName()] = $value;
                }
            }

            return $Seriazable;
        }
    }
?>