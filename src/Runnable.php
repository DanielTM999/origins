<?php

    interface Runnable
    {


        public function run();
    }


    class FunctionalRunnable implements Runnable{
        private $callback;

        public function __construct(callable $callback)
        {
            $this->callback = $callback;
        }

        public function run()
        {
            call_user_func($this->callback);
        }
    }

?>