<?php

namespace MagentoHackathon\Composer\Magento\Event;

use Composer\EventDispatcher\Event;
use MagentoHackathon\Composer\Magento\Deploy\Manager\Entry;
use MagentoHackathon\Composer\Magento\DeployManager\EntryInterface;

/**
 * Class PackageDeployEvent
 * @package MagentoHackathon\Composer\Magento\Event
 * @author  Aydin Hassan <aydin@hotmail.co.uk>
 */
class PackageDeployEvent extends Event
{
    /**
     * @var Entry
     */
    protected $deployEntry;

    /**
     * @param string $name
     * @param EntryInterface $deployEntry
     */
    public function __construct($name, EntryInterface $deployEntry)
    {
        parent::__construct($name);
        $this->deployEntry = $deployEntry;
    }

    /**
     * @return Entry
     */
    public function getDeployEntry()
    {
        return $this->deployEntry;
    }
}