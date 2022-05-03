<?php

namespace Webidea24\DeleteUnusedCatalogImages\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Webidea24\DeleteUnusedCatalogImages\Service\AnalyseService;

class Analyse extends Command
{

    /**
     * @var AnalyseService
     */
    private $analyseService;

    public function __construct(
        AnalyseService $analyseService,
        string $name = null
    )
    {
        parent::__construct($name);
        $this->analyseService = $analyseService;
    }

    protected function configure()
    {
        $this->setName('wi24:unusedCatalogImages:analyse');
        $this->setDescription('Creates a file with files, which would be deleted');
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $file = $this->analyseService->createFile();

        $output->writeln(sprintf('<info>File has been saved to %s</info>', $file));
    }
}
