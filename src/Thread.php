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
            $fileName = "$envPath/thread_task_".uniqid().".php";
            $encodedRunnable = base64_encode(serialize($this->runnable));
            $idThread = $this->idThread;
            $statusFile = $this->statusFile;

            $contentThreadAction = $this->getContentThread($idThread, $statusFile, $encodedRunnable);
            
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

        public function waitUntilFinished(int $timeoutMillis = 100000): void
        {
            $elapsed = 0;
            while (!$this->isFinished()) {
                usleep(100000); 
                $elapsed += 100;
                if ($elapsed >= $timeoutMillis) {
                    throw new \RuntimeException("Thread timeout after {$timeoutMillis}ms");
                }
            }
        }

        public function getActionResult(): string{
            return $this->resultContent ?? null;
        }

        private function getContentThread($idThread, $statusFile, $encodedRunnable): string{
            $contentThreadAction = <<<PHP
                <?php
                session_start();
                require "./vendor/autoload.php";

                use Daniel\Origins\Origin;

                try {
                    \$app = Origin::initialize(true);
                    \$serializableClass = unserialize(base64_decode('$encodedRunnable'));
                    \$serializableClass->run();
                    file_put_contents("$statusFile", "thread_task_status: done_$idThread");
                } catch (Throwable \$e) {
                    file_put_contents("$statusFile", "error: " . \$e->getMessage());
                }

                register_shutdown_function(function() {
                    unlink(__FILE__);
                });
            PHP;

            return  $contentThreadAction;
        }

    }
    

?>