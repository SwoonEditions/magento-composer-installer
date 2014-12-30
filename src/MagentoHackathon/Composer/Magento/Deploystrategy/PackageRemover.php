<?php

namespace MagentoHackathon\Composer\Magento\Deploystrategy;

use MagentoHackathon\Composer\Magento\Util\Filesystem\FileSystem;

/**
 * Class PackageRemover
 * @package MagentoHackathon\Composer\Magento\Deploystrategy
 */
class PackageRemover
{
    /**
     * @var FileSystem
     */
    protected $fileSystem;

    /**
     * @param FileSystem $fileSystem
     */
    public function __construct(FileSystem $fileSystem)
    {
        $this->fileSystem = $fileSystem;
    }

    /**
     * @param array $files
     */
    public function remove(array $files)
    {
         foreach ($files as $file) {
             $this->fileSystem->unlink($file);

             if ($this->fileSystem->isDirEmpty(dirname($file))) {
                 $this->fileSystem->removeDirectory(dirname($file));
             }
         }
    }
}