<?php

namespace MagentoHackathon\Composer\Magento\Parser;

/**
 * Parsers modman files
 */
class ModmanParser implements ParserInterface
{

    /**
     * @var \SplFileObject The modman file
     */
    protected $file;

    /**
     * Constructor
     *
     * @param string $modmanFile
     */
    public function __construct($modmanFile)
    {
        $this->file = new \SplFileObject($modmanFile);
    }

    /**
     * @return array
     * @throws \ErrorException
     */
    public function getMappings()
    {
        if (!$this->file->isReadable()) {
            throw new \ErrorException(sprintf('modman file "%s" not readable', $this->file->getPathname()));
        }

        $map = $this->parseMappings();
        return $map;
    }

    /**
     * @throws \ErrorException
     * @return array
     */
    protected function parseMappings()
    {
        $map = array();
        $line = 0;

        foreach ($this->file as $row) {
            $line++;
            $row = trim($row);
            if ('' === $row || in_array($row[0], array('#', '@'))) {
                continue;
            }
            $parts = preg_split('/\s+/', $row, 2, PREG_SPLIT_NO_EMPTY);
            if (count($parts) != 2) {
                throw new \ErrorException(sprintf('Invalid row on line %d has %d parts, expected 2', $line, count($row)));
            }
            $map[] = $parts;
        }
        return $map;
    }
}
