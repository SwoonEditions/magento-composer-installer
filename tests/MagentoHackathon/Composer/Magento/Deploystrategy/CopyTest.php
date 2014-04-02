<?php
namespace MagentoHackathon\Composer\Magento\Deploystrategy;

class CopyTest extends AbstractTest
{
    /**
     * @param string $src
     * @param string $dest
     * @return Copy
     */
    public function getTestDeployStrategy($src, $dest)
    {
        return new Copy($src, $dest);
    }

    /**
     * @param bool $isDir
     * @return string
     */
    public function getTestDeployStrategyFiletype($isDir = false)
    {
        if ($isDir) return self::TEST_FILETYPE_DIR;

        return self::TEST_FILETYPE_FILE;
    }
    
    public function testCopyDirToDirOfSameName()
    {
        $sourceRoot = 'root';
        $sourceContents = "subdir/subdir/test.xml";

        $this->mkdir($this->sourceDir . DS . $sourceRoot . DS . dirname($sourceContents));
        touch($this->sourceDir . DS . $sourceRoot . DS . $sourceContents);

        // intentionally using a differnt name to verify solution doesn't rely on identical src/dest paths
        $dest = "dest/root";
        $this->mkdir($this->destDir . DS . $dest);

        $testTarget = $this->destDir . DS . $dest . DS . $sourceContents;

        $this->strategy->create($sourceRoot, $dest);
        $this->assertFileExists($testTarget);

        echo "\n\n -- 1st pass tree\n";
        passthru("tree {$this->destDir}/$dest");

        $this->strategy->setIsForced(true);
        $this->strategy->create($sourceRoot, $dest);


        echo "\n\n -- 2nd pass tree\n";
        passthru("tree {$this->destDir}/$dest");

        $this->assertFileNotExists(dirname(dirname($testTarget)) . DS . basename($testTarget));
    }
}
