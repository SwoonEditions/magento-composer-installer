<?php

namespace MagentoHackathon\Composer\Magento\Parser;

/**
 * Class MapParser
 * @package MagentoHackathon\Composer\Magento
 */
class MapParser implements ParserInterface
{
    /**
     * @var array
     */
    protected $mappings = array();

    /**
     * @param array $mappings
     * @param array $translations
     */
    public function __construct(array $mappings, $translations = array())
    {
        parent::__construct($translations);
        $this->setMappings($mappings);
    }

    /**
     * @param array $mappings
     */
    public function setMappings(array $mappings)
    {
        $this->mappings = $this->translatePathMappings($mappings);
    }

    /**
     * @return array
     */
    public function getMappings()
    {
        return $this->mappings;
    }
}
