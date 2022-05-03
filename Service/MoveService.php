<?php

namespace Webidea24\DeleteUnusedCatalogImages\Service;

class MoveService extends AbstractProcessor
{

    /**
    /**
     * @var string
     */
    private $targetRelativeDir;


    protected function _init()
    {
        parent::_init();
        $this->targetRelativeDir = '_unused_images_' . date('Y-m-d_H-i') . '/';
    }

    protected function processFile(string $file): bool
    {
        return $this->mediaWrite->renameFile($file, $this->targetRelativeDir . $file);
    }
}
