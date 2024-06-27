<?php
    $osName = php_uname('s');
    $strPath = __DIR__ . DIRECTORY_SEPARATOR . "shell" . DIRECTORY_SEPARATOR;

    if (strpos($osName, "Windows") !== false) {
        $script = $strPath . "init.bat";
    } else {
        $script = $strPath . "init.sh";
    }

    $output = shell_exec($script);

    echo $output;

?>