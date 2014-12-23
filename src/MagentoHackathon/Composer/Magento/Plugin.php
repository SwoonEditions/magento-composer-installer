<?php

namespace MagentoHackathon\Composer\Magento;

use Composer\Config;
use Composer\Installer;
use MagentoHackathon\Composer\Magento\Event\EventManager;
use MagentoHackathon\Composer\Magento\Event\PackageDeployEvent;
use MagentoHackathon\Composer\Magento\Installer\CoreInstaller;
use MagentoHackathon\Composer\Magento\Installer\MagentoInstallerAbstract;
use MagentoHackathon\Composer\Magento\Installer\ModuleInstaller;
use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;

class Plugin implements PluginInterface
{

    /**
     * @var IOInterface
     */
    protected $io;

    /**
     * @var ProjectConfig
     */
    protected $config;

    /**
     * @var Composer
     */
    protected $composer;

    /**
     * @var DeployManager
     */
    protected $deployManager;

    /**
     * @var EventManager
     */
    protected $eventManager;

    /**
     * init the DeployManagers
     *
     * @param Composer    $composer
     * @param IOInterface $io
     */
    protected function initDeployManager(Composer $composer, IOInterface $io, EventManager $eventManager)
    {
        $this->deployManager = new DeployManager($eventManager);
        $this->deployManager->setSortPriority($this->getSortPriority($composer));

        if ($this->config->hasAutoAppendGitignore()) {
            $gitIgnoreLocation = sprintf('%s/.gitignore', $this->config->getMagentoRootDir());
            $eventManager->listen('post-package-deploy', new GitIgnoreListener(new GitIgnore($gitIgnoreLocation)));
        }

        if ($this->io->isDebug()) {
            $eventManager->listen('pre-package-deploy', function(PackageDeployEvent $event) use ($io) {
                $io->write('Start magento deploy for ' . $event->getDeployEntry()->getPackageName());
            });
        }
    }

    /**
     * get Sort Priority from extra Config
     *
     * @param \Composer\Composer $composer
     *
     * @return array
     */
    private function getSortPriority(Composer $composer)
    {
        $extra = $composer->getPackage()->getExtra();

        return isset($extra[ProjectConfig::SORT_PRIORITY_KEY])
            ? $extra[ProjectConfig::SORT_PRIORITY_KEY]
            : array();
    }

    /**
     * Apply plugin modifications to composer
     *
     * @param Composer    $composer
     * @param IOInterface $io
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        $this->io           = $io;
        $this->composer     = $composer;
        $this->config       = new ProjectConfig($composer->getPackage()->getExtra());

        $this->initDeployManager($composer, $io, $this->getEventManager());

        $this->writeDebug('Activate Magento Composer Installer Plugin');

        $moduleInstaller = new ModuleInstaller($this->io, $composer);
        $moduleInstaller->setDeployManager($this->deployManager);

        $composer->getInstallationManager()->addInstaller($moduleInstaller);
    }

    /**
     * @return EventManager
     */
    public function getEventManager()
    {
        if (!$this->eventManager) {
            $this->eventManager = new EventManager();
        }
        return $this->eventManager;
    }

    /**
     * @param EventManager $eventManager
     */
    public function setEventManager(EventManager $eventManager)
    {
        $this->eventManager = $eventManager;
    }
}


