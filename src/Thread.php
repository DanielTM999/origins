<?php

    namespace Daniel\Origins;

    interface Runnable
    {
        public function run();
    }


    final class Thread
    {
        private string $idThread; 
        private string $statusFile;
        private bool $isFinished;
        private string $resultContent;

        public function __construct(private Runnable $runnable){
            $this->runnable = $runnable;
            $this->idThread = uniqid();
            $this->statusFile = sys_get_temp_dir() . '/thread_' . $this->idThread . '.status';
        }

        public function start(){
            $envPath = $_ENV["base.dir"];
            $serializableClass = serialize($this->runnable);
            $fileName = "$envPath/thread_task_".uniqid().".php";

            $contentThreadAction = '<?php'."\n";
            $contentThreadAction .= 'session_start();'."\n";
            $contentThreadAction .= 'require "./vendor/autoload.php";'."\n";
            $contentThreadAction .= 'use Daniel\Origins\Origin;'."\n";
            $contentThreadAction .= 'try {'."\n";
            $contentThreadAction .= '$app = Origin::initialize(true);'."\n";
            $contentThreadAction .= '$serializableClass = unserialize('."'$serializableClass');\n";
            $contentThreadAction .= '$serializableClass->run();'."\n";
            $contentThreadAction .= '} catch (Exception $e) {'."\n";
            $contentThreadAction .= '}'."\n";
            $contentThreadAction .= 'unlink(__FILE__);'."\n";
            $contentThreadAction .= 'echo "thread_task_status: done_'.$this->idThread.'";'."\n";
            $contentThreadAction .= '?>';

            file_put_contents($fileName, $contentThreadAction);

            $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
            $status = $this->statusFile;
            if ($isWindows) {
                $command = "start /B php $fileName > $status 2>&1";
            } else {
                $command = "nohup php $fileName >> \"$status\" 2>&1 &";
            }

            $finalCommand = sprintf(
                '(cd %s && %s)', 
                escapeshellarg($envPath), 
                $command
            );

            pclose(popen($finalCommand, 'r'));
        }

        public function isFinished(): bool
        {
            if(isset($this->isFinished)){
                return $this->isFinished;
            }
            $exists = file_exists($this->statusFile);
            if($exists){
                $content = file_get_contents($this->statusFile);
                $this->resultContent = $content;
                if (strpos($content, 'thread_task_status: done_'.$this->idThread) !== false) {
                    $this->isFinished = true;
                    unlink($this->statusFile);
                    return true; 
                }else{
                    return false;
                }
            }else{
                return false;
            }
        }

        public function waitUntilFinished(): void
        {
            while (!$this->isFinished()) {
                usleep(100000); 
            }
        }

        public function getActionResult(): string{
            return $this->resultContent ?? null;
        }

    }
    

?>