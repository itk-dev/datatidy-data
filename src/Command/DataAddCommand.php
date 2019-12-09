<?php

/*
 * This file is part of itk-dev/datatidy-data.
 *
 * (c) 2019 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace App\Command;

use App\Data\DataService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DataAddCommand extends Command
{
    protected static $defaultName = 'app:data:add';

    /** @var DataService */
    private $dataService;

    public function __construct(DataService $dataService)
    {
        parent::__construct();
        $this->dataService = $dataService;
    }

    protected function configure()
    {
        $this->addArgument('urls', InputArgument::REQUIRED | InputArgument::IS_ARRAY, 'The data url');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $urls = $input->getArgument('urls');

        foreach ($urls as $url) {
            $output->writeln($url);
            $filename = $this->dataService->add($url);

            $output->writeln(sprintf('Data written to file %s', $filename));
        }

        return 0;
    }
}
