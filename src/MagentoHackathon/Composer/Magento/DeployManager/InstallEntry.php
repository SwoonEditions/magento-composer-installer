<?php
/**
 * 
 * 
 * 
 * 
 */

namespace MagentoHackathon\Composer\Magento\DeployManager;

class InstallEntry implements EntryInterface
{

    protected $packageName;

    /**
     * @var \MagentoHackathon\Composer\Magento\Deploystrategy\DeploystrategyAbstract
     */
    protected $deployStrategy;

    /**
     * @param mixed $packageName
     */
    public function setPackageName($packageName)
    {
        $this->packageName = $packageName;
    }

    /**
     * @return mixed
     */
    public function getPackageName()
    {
        return $this->packageName;
    }

    /**
     * @param \MagentoHackathon\Composer\Magento\Deploystrategy\DeploystrategyAbstract $deployStrategy
     */
    public function setDeployStrategy($deployStrategy)
    {
        $this->deployStrategy = $deployStrategy;
    }

    /**
     * @return \MagentoHackathon\Composer\Magento\Deploystrategy\DeploystrategyAbstract
     */
    public function getDeployStrategy()
    {
        return $this->deployStrategy;
    }

    public function execute()
    {
        $this->deployStrategy->deploy();
    }

    public function getDeployedFiles()
    {
        return $this->deployStrategy->getDeployedFiles();
    }
}
