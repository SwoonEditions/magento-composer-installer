<?php
/**
 * Composer Magento Installer
 */

namespace MagentoHackathon\Composer\Magento\Installer;

use Composer\Repository\InstalledRepositoryInterface;
use Composer\IO\IOInterface;
use Composer\Composer;
use Composer\Installer\LibraryInstaller;
use Composer\Installer\InstallerInterface;
use Composer\Package\PackageInterface;
use InvalidArgumentException;
use MagentoHackathon\Composer\Magento\Deploy\Manager\Entry;
use MagentoHackathon\Composer\Magento\DeployManager;
use MagentoHackathon\Composer\Magento\Deploystrategy\Copy;
use MagentoHackathon\Composer\Magento\Deploystrategy\Link;
use MagentoHackathon\Composer\Magento\Deploystrategy\None;
use MagentoHackathon\Composer\Magento\Deploystrategy\Symlink;
use MagentoHackathon\Composer\Magento\MapParser;
use MagentoHackathon\Composer\Magento\ModmanParser;
use MagentoHackathon\Composer\Magento\PackageXmlParser;
use MagentoHackathon\Composer\Magento\Parser;
use MagentoHackathon\Composer\Magento\ProjectConfig;
use MagentoHackathon\Composer\Magento\Parser\Factory;

/**
 * Composer Magento Installer
 */
class ModuleInstaller extends LibraryInstaller implements InstallerInterface
{

    /**
     * Package Type Definition
     */
    const PACKAGE_TYPE = 'magento-module';

    /**
     * the Default base directory of the magento installation
     */
    const DEFAULT_MAGENTO_ROOT_DIR = 'root';

    /**
     * The base directory of the magento installation
     *
     * @var \SplFileInfo
     */
    protected $magentoRootDir = null;

    /**
     * The base directory of the modman packages
     *
     * @var \SplFileInfo
     */
    protected $modmanRootDir = null;

    /**
     * If set overrides existing files
     *
     * @var bool
     */
    protected $isForced = false;

    /**
     * The module's base directory
     *
     * @var string
     */
    protected $sourceDir;


    protected $deployStrategy = 'symlink';

    /**
     * @var DeployManager
     */
    protected $deployManager;

    /**
     * @var ProjectConfig
     */
    protected $config;

    /**
     * @var Factory
     */
    protected $parserFactory;

    /**
     * Initializes Magento Module installer
     *
     * @param \Composer\IO\IOInterface $io
     * @param \Composer\Composer       $composer
     * @param string                   $type
     *
     * @throws \ErrorException
     */
    public function __construct(IOInterface $io, Composer $composer, $type = 'magento-module', Factory $parserFactory)
    {
        $this->parserFactory = $parserFactory;

        parent::__construct($io, $composer, $type);
        $this->initializeVendorDir();
        $this->config = new ProjectConfig($composer->getPackage()->getExtra());

        if ($this->getConfig()->hasDeployStrategy()) {
            $this->deployStrategy = $this->getConfig()->getDeployStrategy();
        }




        if ((is_null($this->magentoRootDir) || false === $this->magentoRootDir->isDir())
            && $this->deployStrategy != 'none'
        ) {
            $dir = $this->magentoRootDir instanceof \SplFileInfo ? $this->magentoRootDir->getPathname() : '';
            $io->write("<error>magento root dir \"{$dir}\" is not valid</error>", true);
            $io->write(
                '<comment>You need to set an existing path for "magento-root-dir" in your composer.json</comment>', true
            );
            $io->write(
                '<comment>For more information please read about the "Usage" in the README of the installer Package</comment>',
                true
            );
            throw new \ErrorException("magento root dir \"{$dir}\" is not valid");
        }

        if ($this->getConfig()->hasMagentoForce()) {
            $this->isForced = $this->getConfig()->getMagentoForce();
        }

        if ($this->getConfig()->hasDeployStrategy()) {
            $this->setDeployStrategy($this->getConfig()->getDeployStrategy());
        }

        if ($this->getConfig()->hasPathMappingTranslations()) {
            $this->_pathMappingTranslations = $this->getConfig()->getPathMappingTranslations();
        }
    }

    /**
     * @param DeployManager $deployManager
     */
    public function setDeployManager(DeployManager $deployManager)
    {
        $this->deployManager = $deployManager;
    }

    /**
     * @param ProjectConfig $config
     */
    public function setConfig(ProjectConfig $config)
    {
        $this->config = $config;
    }

    /**
     * @return ProjectConfig
     */
    protected function getConfig()
    {
        return $this->config;
    }

    /**
     * @return DeployManager
     */
    public function getDeployManager()
    {
        return $this->deployManager;
    }

    /**
     * @param string $strategy
     */
    public function setDeployStrategy($strategy)
    {
        $this->deployStrategy = $strategy;
    }

    /**
     * Returns the strategy class used for deployment
     *
     * @param \Composer\Package\PackageInterface $package
     * @param string                             $strategy
     *
     * @return \MagentoHackathon\Composer\Magento\Deploystrategy\DeploystrategyAbstract
     */
    public function getDeployStrategy(PackageInterface $package, $strategy = null)
    {
        if (null === $strategy) {
            $strategy = $this->deployStrategy;
        }

        if ($this->getConfig()->hasDeployStrategyOverwrite()) {
            $moduleSpecificDeployStrategys = $this->getConfig()->getDeployStrategyOverwrite();

            if (isset($moduleSpecificDeployStrategys[$package->getName()])) {
                $strategy = $moduleSpecificDeployStrategys[$package->getName()];
            }
        }

        $targetDir = $this->getTargetDir();
        $sourceDir = $this->getSourceDir($package);
        switch ($strategy) {
            case 'copy':
                $impl = new Copy($sourceDir, $targetDir);
                break;
            case 'link':
                $impl = new Link($sourceDir, $targetDir);
                break;
            case 'none':
                $impl = new None($sourceDir, $targetDir);
                break;
            case 'symlink':
            default:
                $impl = new Symlink($sourceDir, $targetDir);
        }
        // Inject isForced setting from extra config
        $impl->setIsForced($this->isForced);
        $impl->setIgnoredMappings($this->getModuleSpecificDeployIgnores($package));

        return $impl;
    }
    
    protected function getModuleSpecificDeployIgnores($package)
    {

        $moduleSpecificDeployIgnores = array();
        if ($this->getConfig()->hasMagentoDeployIgnore()) {
            $magentoDeployIgnore = $this->getConfig()->getMagentoDeployIgnore();
            if (isset($magentoDeployIgnore['*'])) {
                $moduleSpecificDeployIgnores = $magentoDeployIgnore['*'];
            }
            if (isset($magentoDeployIgnore[$package->getName()])) {
                $moduleSpecificDeployIgnores = array_merge(
                    $moduleSpecificDeployIgnores,
                    $magentoDeployIgnore[$package->getName()]
                );
            }
        }
        return $moduleSpecificDeployIgnores;
    }

    /**
     * Return Source dir of package
     *
     * @param \Composer\Package\PackageInterface $package
     *
     * @return string
     */
    protected function getSourceDir(PackageInterface $package)
    {
        $this->filesystem->ensureDirectoryExists($this->vendorDir);

        return $this->getInstallPath($package);
    }

    /**
     * Return the absolute target directory path for package installation
     *
     * @return string
     */
    protected function getTargetDir()
    {
        $targetDir = realpath($this->magentoRootDir->getPathname());

        return $targetDir;
    }

    /**
     * Installs specific package
     *
     * @param InstalledRepositoryInterface $repo    repository in which to check
     * @param PackageInterface             $package package instance
     */
    public function install(InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        parent::install($repo, $package);

        $strategy = $this->getDeployStrategy($package);
        $strategy->setMappings($this->getParser($package)->getMappings());
        $deployManagerEntry = new Entry();
        $deployManagerEntry->setPackageName($package->getName());
        $deployManagerEntry->setDeployStrategy($strategy);
        $this->deployManager->addPackage($deployManagerEntry);
    }

    /**
     * Updates specific package
     *
     * @param InstalledRepositoryInterface $repo    repository in which to check
     * @param PackageInterface             $initial already installed package version
     * @param PackageInterface             $target  updated version
     *
     * @throws InvalidArgumentException if $from package is not installed
     */
    public function update(InstalledRepositoryInterface $repo, PackageInterface $initial, PackageInterface $target)
    {
        $initialStrategy = $this->getDeployStrategy($initial);
        $initialStrategy->setMappings($this->getParser($initial)->getMappings());
        $initialStrategy->clean();

        parent::update($repo, $initial, $target);

        $targetStrategy = $this->getDeployStrategy($target);
        $targetStrategy->setMappings($this->getParser($target)->getMappings());
        $deployManagerEntry = new Entry();
        $deployManagerEntry->setPackageName($target->getName());
        $deployManagerEntry->setDeployStrategy($targetStrategy);
        $this->deployManager->addPackage($deployManagerEntry);
    }

    /**
     * Uninstalls specific package.
     *
     * @param InstalledRepositoryInterface $repo    repository in which to check
     * @param PackageInterface             $package package instance
     */
    public function uninstall(InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        $strategy = $this->getDeployStrategy($package);
        $strategy->setMappings($this->getParser($package)->getMappings());
        $strategy->clean();

        parent::uninstall($repo, $package);
    }



    /**
     * {@inheritDoc}
     */
    public function getInstallPath(PackageInterface $package)
    {

        if (!is_null($this->modmanRootDir) && true === $this->modmanRootDir->isDir()) {
            $targetDir = $package->getTargetDir();
            if (!$targetDir) {
                list($vendor, $targetDir) = explode('/', $package->getPrettyName());
            }
            $installPath = $this->modmanRootDir . '/' . $targetDir;
        } else {
            $installPath = parent::getInstallPath($package);
        }

        // Make install path absolute. This is needed in the symlink deploy strategies.
        if (DIRECTORY_SEPARATOR !== $installPath[0] && $installPath[1] !== ':') {
            $installPath = getcwd() . "/$installPath";
        }

        return $installPath;
    }

    public function transformArrayKeysToLowerCase($array)
    {
        $arrayNew = array();
        foreach ($array as $key => $value) {
            $arrayNew[strtolower($key)] = $value;
        }

        return $arrayNew;
    }

    /**
     * joinFilePath
     *
     * joins 2 Filepaths and replaces the Directory Separators
     * with the Systems Directory Separator
     *
     * @param $path1
     * @param $path2
     *
     * @return string
     */
    public function joinFilePath($path1, $path2)
    {
        $prefix = $this->startsWithDs($path1) ? DIRECTORY_SEPARATOR : '';
        $suffix = $this->endsWithDs($path2) ? DIRECTORY_SEPARATOR : '';

        return $prefix . implode(
            DIRECTORY_SEPARATOR,
            array_merge(
                preg_split('/\\\|\//', $path1, null, PREG_SPLIT_NO_EMPTY),
                preg_split('/\\\|\//', $path2, null, PREG_SPLIT_NO_EMPTY)
            )
        ) . $suffix;
    }

    /**
     * startsWithDs
     *
     * @param $path
     *
     * @return bool
     */
    protected function startsWithDs($path)
    {
        return strrpos($path, '/', -strlen($path)) !== FALSE
            || strrpos($path, '\\', -strlen($path)) !== FALSE;
    }

    /**
     * endsWithDs
     *
     * @param $path
     *
     * @return bool
     */
    protected function endsWithDs($path)
    {
        return strpos($path, '/', strlen($path) - 1) !== FALSE
            || strpos($path, '\\', strlen($path) - 1) !== FALSE;
    }

    /**
     * Decides if the installer supports the given type
     *
     * @param  string $packageType
     *
     * @return bool
     */
    public function supports($packageType)
    {
        return self::PACKAGE_TYPE === $packageType;
    }
}
