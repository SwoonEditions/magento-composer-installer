<?php

namespace MagentoHackathon\Composer\Magento\DeployManager;

/**
 * Interface EntryInterface
 * @package MagentoHackathon\Composer\Magento\DeployManager
 */
interface EntryInterface
{
    /**
     * @return string
     */
    public function getPackageName();

    /**
     * Execute the entry
     */
    //public function execute();
}