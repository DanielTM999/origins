<?php

namespace Daniel\Origins\Tester;

use Daniel\Origins\ServerAutoload;
use Override;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class ServerAutoloadTester extends ServerAutoload
{
    #[Override]
    public function load(): void
    {
        parent::load();
        $this->loadTestFolder();
    }

    private function loadTestFolder(): void
    {
        $folder = $this->getTestFolder();
        if ($folder === null || !is_dir($folder)) {
            return;
        }

        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($folder, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && strtolower($file->getExtension()) === 'php') {
                $files[] = $file->getPathname();
            }
        }

        if (!empty($files)) {
            $this->loadWithDependencies($files);
        }
    }
}
