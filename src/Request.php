<?php
    namespace Daniel\Origins;

    class Request{
        private $pathVar;
        private $headers;
        private $body;
        private string $path;
        private string $host;
        
        public function __construct($headers, $body, $pathVar, string $path, string $host)
        {
            $this->headers = $headers;
            $this->body = $body;
            $this->pathVar = $pathVar;
            $this->path = $path;
            $this->host = $host;
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

        public function getPath(): string{
            return $this->path;
        }

        public function getHostClient(): string{
            return $this->host;
        }

        public function getPathVar(string $varName = null){
            if(isset($varName)){
                return $this->pathVar[$varName] ?? null;
            }else{
                return $this->pathVar ?? [];
            }
        }

    }

    class Response{

        public function renderPage(string $page, $viewModel = null): void{
            if ($viewModel !== null) {
                $GLOBALS['model'] = $viewModel;
            }
            include_once $page;
        }
        public function redirect(string $to): void{
            header("Location: $to");
            exit;
        }
    }

?>