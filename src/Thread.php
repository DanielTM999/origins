<?php

    final class Thread
    {

        private string $statusFile;

        public function __construct(private Runnable $runnable){
            $this->runnable = $runnable;
            $this->statusFile = sys_get_temp_dir() . '/thread_' . uniqid() . '.status';
        }

        public function start(){
            $serializedRunnable = base64_encode(serialize($this->runnable));
            $phpBinary = PHP_BINARY;
            $currentFile = __FILE__;
            $statusFile = $this->statusFile;

            if (strncasecmp(PHP_OS, 'WIN', 3) == 0) {
                pclose(popen("start /B \"$phpBinary\" -r \"require '$currentFile'; \$runnable = unserialize(base64_decode('$serializedRunnable')); \$runnable->run(); file_put_contents('$statusFile', 'done');\"", "r"));
            } else {
                shell_exec("$phpBinary -r \"require '$currentFile'; \$runnable = unserialize(base64_decode('$serializedRunnable')); \$runnable->run(); file_put_contents('$statusFile', 'done');\" > /dev/null 2>&1 &");
            }
        }

        public function isFinished(): bool
        {
            return file_exists($this->statusFile);
        }

        public function waitUntilFinished(): void
        {
            while (!$this->isFinished()) {
                usleep(100000); 
            }
        }
    }
    

?>