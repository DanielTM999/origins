<?php
    require './vendor/autoload.php';
    include "./Teste.php";

    use Daniel\Origins\Origins;


    $app = Origins::initialize();
    $app->AddDependency(TesteService::class);
    $app->EnableEndPoint();
    $app->Run();

?>
