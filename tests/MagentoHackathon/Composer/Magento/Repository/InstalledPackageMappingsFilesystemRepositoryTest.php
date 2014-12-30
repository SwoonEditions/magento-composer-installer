<?php

namespace MagentoHackathon\Composer\Magento\Repository;
use org\bovigo\vfs\vfsStream;

/**
 * Class InstalledPackageMappingsFilesystemRepositoryTest
 * @package MagentoHackathon\Composer\Magento\Repository
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class InstalledPackageMappingsFilesystemRepositoryTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var InstalledPackageMappingsFilesystemRepository
     */
    protected $repository;

    /**
     * @var string
     */
    protected $filePath;
    protected $root;

    public function setUp()
    {
        $this->root         = vfsStream::setup('root');
        $this->filePath     = vfsStream::url('root/mappings.json');
        $this->repository   = new InstalledPackageMappingsFilesystemRepository($this->filePath);
    }

    public function testExceptionIsThrownIfDbFileExistsButIsNotWritable()
    {
        vfsStream::newFile('mappings.json')->at($this->root);
        chmod($this->filePath, 0400);
        $this->setExpectedException('Exception', 'File "vfs://root/mappings.json" is not writable');
        new InstalledPackageMappingsFilesystemRepository($this->filePath);
    }

    public function testExceptionIsThrownIfDbFileExistsButIsNotReadable()
    {
        vfsStream::newFile('mappings.json')->at($this->root);
        chmod($this->filePath, 0200);
        $this->setExpectedException('Exception', 'File "vfs://root/mappings.json" is not readable');
        new InstalledPackageMappingsFilesystemRepository($this->filePath);
    }

    public function testExceptionIsThrownIfDbDoesNotExistAndFolderIsNotWritable()
    {
        chmod(dirname($this->filePath), 0400);
        $this->setExpectedException('Exception', 'Directory "vfs://root" is not writable');
        new InstalledPackageMappingsFilesystemRepository($this->filePath);
    }

    public function testGetInstalledMappingsThrowsExceptionIfPackageNotFound()
    {
        $this->setExpectedException('Exception', 'Package Mappings for: "not-here" not found');
        $this->repository->getInstalledMappings('not-here');
    }

    public function testGetInstalledMappingsReturnsMappingsCorrectly()
    {
        $mappings = array(
            array(1, 1),
            array(2, 2),
            array(3, 3),
        );

        file_put_contents($this->filePath, json_encode(array('some-package' => $mappings)));
        $this->assertEquals($mappings, $this->repository->getInstalledMappings('some-package'));
    }

    public function testExceptionIsThrownIfDuplicatePackageIsAdded()
    {
        $this->setExpectedException('Exception', 'Package Mappings for: "some-package" are already present');
        $this->repository->addInstalledMappings('some-package', array());
        $this->repository->addInstalledMappings('some-package', array());
    }

    public function testAddInstalledMappings()
    {
        $mappings = array(
            array(1, 1),
            array(2, 2),
            array(3, 3),
        );

        $this->repository->addInstalledMappings('some-package', $mappings);
        unset($this->repository);
        $this->assertEquals(array('some-package' => $mappings), json_decode(file_get_contents($this->filePath), true));
    }

    public function testExceptionIsThrownIfRemovingMappingsWhichDoNotExist()
    {
        $this->setExpectedException('Exception', 'Package Mappings for: "some-package" not found');
        $this->repository->removeInstalledMappings('some-package', array());
    }

    public function testCanSuccessfullyRemovePackageMappings()
    {
        $this->repository->addInstalledMappings('some-package', array());
        $this->repository->removeInstalledMappings('some-package', array());
    }

    public function testFileIsNotWrittenIfNoChanges()
    {
        $mappings = array(
            array(1, 1),
            array(2, 2),
            array(3, 3),
        );

        file_put_contents($this->filePath, json_encode(array('some-package' => $mappings)));
        $writeTime = filemtime($this->filePath);
        unset($this->repository);
        clearstatcache();

        $this->assertEquals($writeTime, filemtime($this->filePath));
    }

    public function tearDown()
    {
        unset($this->repository);
    }
}
