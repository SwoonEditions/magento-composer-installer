<?php

namespace MagentoHackathon\Composer\Magento\Parser;

use Composer\Package\PackageInterface;
use Composer\Package\RootPackageInterface;
use MagentoHackathon\Composer\Magento\MapParser;
use MagentoHackathon\Composer\Magento\ModmanParser;
use MagentoHackathon\Composer\Magento\PackageXmlParser;
use MagentoHackathon\Composer\Magento\ProjectConfig;

/**
 * Class Factory
 */
class Factory
{

    /**
     * @var ProjectConfig
     */
    protected $config;

    /**
     * @param ProjectConfig $config
     */
    public function __construct(ProjectConfig $config)
    {
        $this->config = $config;
    }

    /**
     * Returns the modman parser for the vendor dir
     *
     * @param PackageInterface     $package
     * @param RootPackageInterface $rootPackage
     * @param string               $packageSourceDirectory
     *
     * @return Parser
     * @throws \ErrorException
     */
    public function getParser(PackageInterface $package, RootPackageInterface $rootPackage, $packageSourceDirectory)
    {
        $extra = $package->getExtra();
        $moduleSpecificMap = $rootPackage->getExtra();
        if (isset($moduleSpecificMap['magento-map-overwrite'])) {
            $moduleSpecificMap = array_change_key_case($moduleSpecificMap['magento-map-overwrite'], CASE_LOWER);
            if (isset($moduleSpecificMap[$package->getName()])) {
                $map = $moduleSpecificMap[$package->getName()];
                return new MapParser($map);
            }
        }

        if (isset($extra['map'])) {
            return new MapParser($extra['map']);
        } elseif (isset($extra['package-xml'])) {
            return new PackageXmlParser(sprintf('%s/%s/', $packageSourceDirectory, $extra['package-xml']));
        } elseif (file_exists(sprintf('%s/%s', $packageSourceDirectory, '/modman'))) {
            return new ModmanParser(sprintf('%s/%s', $packageSourceDirectory, '/modman'));
        }

        throw new \ErrorException(sprintf('Unable to find deploy strategy for module: "%s"', $package->getName()));
    }
}