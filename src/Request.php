<?php
    namespace Daniel\Origins;

    class Request{
        private $pathVar;
        private $headers;
        private $body;
        
        public function __construct($headers, $body, $pathVar)
        {
            $this->headers = $headers;
            $this->body = $body;
            $this->pathVar = $pathVar;
        }

        public function getHeaders(){
            return $this->headers;
        }

        public function getHeader(string $headerName){
            if (isset($this->headers[$headerName])) {
                $result = $this->headers[$headerName];
            } else {
                $result = null;
            }
            return $result;
        }

        public function getBody(){
            return $this->body;
        }

        public function getPathVar(){
            return $this->pathVar;
        }

    }

?>