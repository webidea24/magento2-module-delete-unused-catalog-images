<?php

namespace Webidea24\DeleteUnusedCatalogImages\Console\Command;

class Delete extends AbstractProcess
{
    protected function configure()
    {
        $this->setName('wi24:unusedCatalogImages:delete');
        $this->setDescription('Delete all unused images');
        parent::configure();
    }
}
