<?php

    namespace Daniel\Origins;

    final class ViewModel
    {
        private static $model;
        
        public static function Model(){
            return self::$model;
        }

        public static function setModel($model){
            self::$model = $model;
        }
    }
    

?>