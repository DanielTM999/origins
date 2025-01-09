<?php
    
    $projectRoot = getBaseDir();

    $projectName = basename($projectRoot);

    $htaccessContent = <<<HTACCESS
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ index.php [L]
    HTACCESS;

    $envContent = <<<ENV
    enviroment=dev
    log.path=/log
    server.name=$projectName
    ENV;

    $indexContent = <<<INDEX
    <?php
        require "./vendor/autoload.php";
        use Daniel\\Origins\\Origin;
        \$app = Origin::initialize();
        \$app->run();
    ?>
    INDEX;



    file_put_contents('.htaccess', $htaccessContent);
    file_put_contents('.env', $envContent);
    file_put_contents('index.php', $indexContent);
    echo $projectRoot;

    echo "Arquivos .htaccess e index.php criados com sucesso!\n";
    
    function getBaseDir(): string{
        $dirLibrary = __DIR__;

        
        while (strpos($dirLibrary, 'vendor') !== false) {
            $dirLibrary = dirname($dirLibrary);
        }

        $dirBase = $dirLibrary;
        
        $vendorPos = strpos($dirBase, '\vendor');
        if ($vendorPos !== false) {
            $dirBase = substr($dirBase, 0, $vendorPos);
        }

        if(strpos($dirBase, '\src') !== false){    
            $dirBase = dirname($dirBase);
            $dirBase = dirname($dirBase);
            echo $dirBase;
        }
        return $dirBase;
    }
    
    // require "./vendor/autoload.php";

    // use Daniel\Origins\Controller;
    // use Daniel\Origins\ControllerAdvice;
    // use Daniel\Origins\Get;
    // use Daniel\Origins\Origin;
    // use Daniel\Origins\Request;
    // use Daniel\Origins\Response;

    // Origin::initialize()->run();

    // #[Controller]
    // final class indexController
    // {

    //     #[Get("/")]
    //     function index(Request $request, Response $response, $teste){
            
    //     }
    // }
?>

