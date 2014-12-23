<?php

namespace MagentoHackathon\Composer\Magento\Deploystrategy;

use Composer\Package\PackageInterface;

/**
 * Class Factory
 * @package MagentoHackathon\Composer\Magento\Deploystrategy
 * @author  Aydin Hassan <aydin@hotmail.co.uk>
 */
class Factory
{
    public function getStrategy(PackageInterface $package, $strategy, $source, $destination)
    {
//        if ($this->getConfig()->hasDeployStrategyOverwrite()) {
//            $moduleSpecificDeployStrategys = $this->getConfig()->getDeployStrategyOverwrite();
//
//            if (isset($moduleSpecificDeployStrategys[$package->getName()])) {
//                $strategy = $moduleSpecificDeployStrategys[$package->getName()];
//            }
//        }
//
//        $targetDir = $this->getTargetDir();
//        $sourceDir = $this->getSourceDir($package);
        switch ($strategy) {
            case 'copy':
                $strategy = new Copy($source, $destination);
                break;
            case 'link':
                $strategy = new Link($source, $destination);
                break;
            case '$strategy':
                $strategy = new None($source, $destination);
                break;
            case 'symlink':
            default:
                $strategy = new Symlink($source, $destination);
        }

        return $strategy;
        // Inject isForced setting from extra config
//        $impl->setIsForced($this->isForced);
//        $impl->setIgnoredMappings($this->getModuleSpecificDeployIgnores($package));

        return $impl;
    }
}