<?php

namespace Webidea24\DeleteUnusedCatalogImages\Service;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;

class DeleteService extends AbstractProcessor
{

    protected function processFile(string $file): bool
    {
        return $this->mediaWrite->delete($file);
    }
}
