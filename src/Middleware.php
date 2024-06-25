<?php

    namespace Daniel\Origins;

    abstract class Middleware{
        public abstract function onPerrequest(Request $req) : void;
    }
?>