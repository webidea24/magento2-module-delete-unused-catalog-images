<?php

namespace Webidea24\DeleteUnusedCatalogImages\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Webidea24\DeleteUnusedCatalogImages\Service\AbstractProcessor;
use Webidea24\DeleteUnusedCatalogImages\Service\AnalyseService;

abstract class AbstractProcess extends Command
{

    /**
     * @var AnalyseService
     */
    private $analyseService;

    /**
     * @var AbstractProcessor
     */
    private $processor;

    public function __construct(
        AnalyseService $analyseService,
        AbstractProcessor $processor,
        string $name = null
    )
    {
        parent::__construct($name);
        $this->analyseService = $analyseService;
        $this->processor = $processor;
    }

    protected function configure()
    {
        $this->addOption('dry-run', '-t', InputOption::VALUE_NONE, 'Enable dry mode, to output the count of files, which would be processed');
        $this->addOption('debug', '-d', InputOption::VALUE_NONE, 'Enable debug mode. This will output all files which has been processed (compatible with dry-mode)');
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $file = $this->analyseService->getLastFile();

        $this->processor->setDryRun($input->getOption('dry-run'));
        $results = $this->processor->execute($file);

        $output->writeln(sprintf('<info>%s has been processed</info>', $results['success']['count']));
        $this->debugFiles($input, $output, $results['success']['files']);
        $output->writeln(sprintf('<error>%s has been failed</error>', $results['failed']['count']));
        $this->debugFiles($input, $output, $results['failed']['files']);
        $output->writeln(sprintf('<comment>%s were not found</comment>', $results['notFound']['count']));
        $this->debugFiles($input, $output, $results['notFound']['files']);
    }

    private function debugFiles(InputInterface $input, OutputInterface $output, array $files)
    {
        if (!$input->getOption('debug')) {
            return;
        }

        foreach ($files as $file) {
            $output->writeln(sprintf("\tFile: %s", $file));
        }
    }
}
