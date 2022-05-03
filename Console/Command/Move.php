<?php

namespace Webidea24\DeleteUnusedCatalogImages\Console\Command;

class Move extends AbstractProcess
{
    protected function configure()
    {
        $this->setName('wi24:unusedCatalogImages:move');
        $this->setDescription('Moves all unused images');

        parent::configure();
    }
}
