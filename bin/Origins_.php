#!/usr/bin/env php
<?php

    require __DIR__ . "../vendor/autoload.php";

    if ($argc < 2) {
        echo "Uso: php origins <comando>\n";
        exit(1);
    }
    
    $command = $argv[1];
    
    switch ($command) {
        case 'new':
            if (empty($argv[2])) {
                echo "Erro: Você precisa fornecer um nome para o projeto.\n";
                exit(1);
            }
    
            $projectName = $argv[2];
            $projectPath = getcwd() . "/$projectName";
    
            if (is_dir($projectPath)) {
                echo "Erro: O diretório '$projectName' já existe.\n";
                exit(1);
            }
    
            echo "Criando novo projeto '$projectName'...\n";
    
            mkdir($projectPath);
            file_put_contents("$projectPath/index.php", "<?php\n echo 'Hello, Origins!';\n");

            mkdir("$projectPath/src"); 
            mkdir("$projectPath/src/controllers");
            mkdir("$projectPath/src/views"); 
            mkdir("$projectPath/src/models"); 

            echo "Projeto '$projectName' criado com sucesso! 🎉\n";
            echo "Estrutura criada:\n";
            echo "  $projectName/\n";
            echo "  ├── index.php\n";
            echo "  ├── src/\n";
            echo "  │   ├── controllers/\n";
            echo "  │   ├── views/\n";
            echo "  │   ├── models/\n";

            break;
    
        
            default:
            echo "Comando desconhecido: $command\n";
            echo "Comandos disponíveis:\n";
            echo "  new <nome_do_projeto> - Cria um novo projeto\n";
            exit(1);
    }
    

?>