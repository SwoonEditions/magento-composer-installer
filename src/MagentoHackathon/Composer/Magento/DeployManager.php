<?php
/**
 * 
 * 
 * 
 * 
 */

namespace MagentoHackathon\Composer\Magento;

use Composer\IO\IOInterface;
use Composer\Repository\InstalledRepositoryInterface;
use MagentoHackathon\Composer\Magento\Deploy\Manager\Entry;
use MagentoHackathon\Composer\Magento\DeployManager\InstallEntry;
use MagentoHackathon\Composer\Magento\DeployManager\RemoveEntry;
use MagentoHackathon\Composer\Magento\Deploystrategy\Copy;
use MagentoHackathon\Composer\Magento\Deploystrategy\PackageRemover;
use MagentoHackathon\Composer\Magento\Event\EventManager;
use MagentoHackathon\Composer\Magento\Event\PackageDeployEvent;
use MagentoHackathon\Composer\Magento\Repository\InstalledFilesRepositoryInterface;
use MagentoHackathon\Composer\Magento\Repository\InstalledPackageMappingsFilesystemRepository;
use MagentoHackathon\Composer\Magento\Repository\InstalledPackageMappingsRepositoryInterface;

class DeployManager
{

    const SORT_PRIORITY_KEY = 'magento-deploy-sort-priority';

    /**
     * @var Entry[]
     */
    protected $packages = array();

    /**
     * @var IOInterface
     */
    protected $io;

    /**
     * an array with package names as key and priorities as value
     * 
     * @var array
     */
    protected $sortPriority = array();

    /**
     * @var EventManager
     */
    protected $eventManager;

    /**
     * @var InstalledFilesRepositoryInterface
     */
    protected $repository;

    protected $installs = array();

    protected $unInstalls = array();

    /**
     * @var PackageRemover
     */
    protected  $packageRemover;

    /**
     * @param EventManager $eventManager
     * @param InstalledFilesRepositoryInterface $repository
     * @param PackageRemover $packageRemover
     */
    public function __construct(
        EventManager $eventManager,
        InstalledFilesRepositoryInterface $repository,
        PackageRemover $packageRemover
    ) {
        $this->eventManager = $eventManager;
        $this->repository   = $repository;
        $this->packageRemover = $packageRemover;
    }

    /**
     * @param Entry $package
     */
    public function addPackage(Entry $package)
    {
        $this->packages[] = $package;
    }

    /**
     * @param $priorities
     */
    public function setSortPriority($priorities)
    {
        $this->sortPriority = $priorities;
    }

    /**
     * uses the sortPriority Array to sort the packages.
     * Highest priority first.
     * Copy gets per default higher priority then others
     */
    protected function sortPackages()
    {
        $sortPriority = $this->sortPriority;
        $getPriorityValue = function( Entry $object ) use ( $sortPriority ){
            $result = 100;
            if( isset($sortPriority[$object->getPackageName()]) ){
                $result = $sortPriority[$object->getPackageName()];
            }elseif( $object->getDeployStrategy() instanceof Copy ){
                $result = 101;
            }
            return $result;
        };
        usort( 
            $this->packages, 
            function($a, $b)use( $getPriorityValue ){
                /** @var Entry $a */
                /** @var Entry $b */
                $aVal = $getPriorityValue($a);
                $bVal = $getPriorityValue($b);
                if ($aVal == $bVal) {
                    return 0;
                }
                return ($aVal > $bVal) ? -1 : 1;
            }
        );
    }

    /**
     * Run all uninstalls and installs
     */
    public function execute()
    {
        $this->unInstall();
        $this->install();
    }

    /**
     * Install all the queued packages
     */
    public function install()
    {
        /** @var InstallEntry $entry */
        foreach ($this->installs as $entry) {
            $this->eventManager->dispatch(new PackageDeployEvent('pre-package-install', $entry));
            $entry->getDeployStrategy()->deploy();

            $this->repository->add(
                $entry->getPackageName(),
                $entry->getDeployStrategy()->getDeployedFiles()
            );

            $this->eventManager->dispatch(new PackageDeployEvent('pre-package-install', $entry));
        }
    }

    /**
     * Uninstall all the queued packages
     */
    public function unInstall()
    {
        /** @var RemoveEntry $entry */
        foreach ($this->unInstalls as $entry) {
            $this->eventManager->dispatch(new PackageDeployEvent('pre-package-uninstall', $entry));

            $installedFiles = $this->repository->getByPackage($entry->getPackageName());
            $this->packageRemover->remove($installedFiles);
            $this->repository->removeByPackage($entry->getPackageName());

            $this->eventManager->dispatch(new PackageDeployEvent('post-package-uninstall', $entry));
        }
    }
}
