<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Deletes abandoned preview uploads under writable/uploads/tmp/ — files
 * left behind when an admin uploads an Excel/photo zip for preview but
 * never confirms the import (or the browser tab is closed, an error
 * occurs, etc). Nothing else ever cleans these up. Intended to run daily
 * via cron: `php spark uploads:clean-tmp`.
 */
class CleanTmpUploadsCommand extends BaseCommand
{
    protected $group = 'Maintenance';
    protected $name = 'uploads:clean-tmp';
    protected $description = 'Deletes preview-upload temp files older than 24 hours.';

    public function run(array $params)
    {
        $tmpDir = WRITEPATH . 'uploads/tmp/';
        if (! is_dir($tmpDir)) {
            CLI::write('No tmp directory — nothing to do.', 'dark_gray');

            return;
        }

        $cutoff = time() - 86400;
        $removed = 0;

        foreach (new \DirectoryIterator($tmpDir) as $item) {
            if ($item->isDot()) {
                continue;
            }
            if ($item->getMTime() >= $cutoff) {
                continue;
            }

            if ($item->isDir()) {
                $this->deleteDirectory($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
            $removed++;
        }

        CLI::write("Removed {$removed} stale temp item(s).", 'green');
    }

    private function deleteDirectory(string $dir): void
    {
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($dir);
    }
}
