<?php

    namespace Daniel\Origins;

    use ReflectionClass;

    class Runnable
    {
        public function run(){}
    }


    final class Thread
    {

        private string $statusFile;
        private string $tempScriptFile;

        public function __construct(private Runnable $runnable){
            $this->runnable = $runnable;
            $this->statusFile = sys_get_temp_dir() . '/thread_' . uniqid() . '.status';
            $this->tempScriptFile = sys_get_temp_dir() . '/thread_' . uniqid() . '.php';
        }

        public function start(){
            $reflection = new ReflectionClass($this->runnable);
            $reflectionRunnable = new ReflectionClass(Runnable::class);
            $filePath = $reflection->getFileName();
            $filePathRunnable = $reflectionRunnable->getFileName();
            $filePathRunnableModified = str_replace('\\', '/', $filePathRunnable);

            $tempScriptFile = sys_get_temp_dir() . '/temp_script_' . uniqid() . '.php';                     
            $content = file_get_contents($filePath);

            $contentWithoutTags = preg_replace('/<\?php|\?>/', '', $content);
            $includes = [
                'include "' . $filePathRunnableModified . '";',
            ];
            $contentWithIncludes = implode("\n", $includes) . "\n" . $contentWithoutTags;
            $instance = '$run = new '.$reflection->getName()."();\n".'$run->run();';
            $finalContent = "<?php\n" . $contentWithIncludes . "\n" . $instance ."\n?>";

            file_put_contents($tempScriptFile, $finalContent);

            $status = $this->statusFile; 
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                $command = "start /B php $tempScriptFile > $status 2>&1";
            } else {
                $command = "nohup php $tempScriptFile >> \"$status\" 2>&1 &";
            }

            pclose(popen($command, 'r'));
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

        private function escapePath(string $path): string
        {
            return str_replace("'", "\\'", $path);
        }

    }
    

?>