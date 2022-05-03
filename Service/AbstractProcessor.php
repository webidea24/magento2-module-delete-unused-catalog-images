<?php

namespace Webidea24\DeleteUnusedCatalogImages\Service;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;

abstract class AbstractProcessor
{

    /**
     * @var Filesystem
     */
    protected $fileSystem;

    /**
     * @var Filesystem\Directory\WriteInterface
     */
    protected $mediaWrite;

    /**
     * @var bool
     */
    private $isDryRun = false;

    public function __construct(Filesystem $fileSystem)
    {
        $this->fileSystem = $fileSystem;
        $this->_init();
    }

    protected function _init()
    {
        $this->mediaWrite = $this->fileSystem->getDirectoryWrite(DirectoryList::MEDIA);
    }

    final public function setDryRun(bool $isDryRun)
    {
        $this->isDryRun = $isDryRun;
    }

    final public function execute(string $analyseFile): array
    {
        $defaultArray = ['count' => 0, 'files' => []];
        $results = ['success' => $defaultArray, 'failed' => $defaultArray, 'notFound' => $defaultArray];

        $reader = $this->fileSystem->getDirectoryRead(DirectoryList::VAR_DIR);
        $lines = explode("\n", $reader->openFile($reader->getRelativePath($analyseFile))->readAll());

        foreach ($lines as $line) {
            if (empty($line)) {
                continue;
            }

            if ($this->mediaWrite->isFile($line)) {
                if ($this->isDryRun || $this->processFile($line)) {
                    $results['success']['count']++;
                    $results['success']['files'][] = $line;
                } else {
                    $results['failed']['count']++;
                    $results['failed']['files'][] = $line;
                }
            } else {
                $results['notFound']['count']++;
                $results['notFound']['files'][] = $line;
            }
        }

        return $results;
    }

    abstract protected function processFile(string $file): bool;
}
