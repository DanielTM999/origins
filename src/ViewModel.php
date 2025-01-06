<?php

    namespace Daniel\Origins;

    final class ViewModel
    {
        public function __construct($viewModel = null) {
            $_SESSION["ViewModel.viewModel"] = $viewModel;
        }  

        public static function Model(){
            return $_SESSION["ViewModel.viewModel"];
        }

    }
    

?>