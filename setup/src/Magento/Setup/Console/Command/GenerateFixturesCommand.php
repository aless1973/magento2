<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Setup\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Setup\Fixtures\FixtureModel;

/**
 * Command generates fixtures for performance tests
 */
class GenerateFixturesCommand extends Command
{
    /**
     * Profile argument
     */
    const PROFILE_ARGUMENT = 'profile';

    /**
     * @var FixtureModel
     */
    private $fixtureModel;

    /**
     * @param FixtureModel $fixtureModel
     */
    public function __construct(FixtureModel $fixtureModel)
    {
        $this->fixtureModel = $fixtureModel;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('setup:performance:generate-fixtures')
            ->setDescription('Generates fixtures')
            ->setDefinition([
                new InputArgument(
                    self::PROFILE_ARGUMENT,
                    InputArgument::REQUIRED,
                    'Path to profile configuration file'
                ),
            ]);
        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $totalStartTime = microtime(true);

            $fixtureModel = $this->fixtureModel;
            $fixtureModel->initObjectManager();
            $fixtureModel->loadFixtures();
            $fixtureModel->loadConfig($input->getArgument(self::PROFILE_ARGUMENT));

            $output->writeln('<info>Generating profile with following params:</info>');

            foreach ($fixtureModel->getParamLabels() as $configKey => $label) {
                $output->writeln('<info> |- ' . $label . ': ' . $fixtureModel->getValue($configKey) . '</info>');
            }

            /** @var $config \Magento\Indexer\Model\Config */
            $config = $fixtureModel->getObjectManager()->get('Magento\Indexer\Model\Config');
            $indexerListIds = $config->getIndexers();
            /** @var $indexerRegistry \Magento\Indexer\Model\IndexerRegistry */
            $indexerRegistry = $fixtureModel->getObjectManager()->create('Magento\Indexer\Model\IndexerRegistry');
            $indexersState = [];
            foreach ($indexerListIds as $indexerId) {
                $indexer = $indexerRegistry->get($indexerId['indexer_id']);
                $indexersState[$indexerId['indexer_id']] = $indexer->isScheduled();
                $indexer->setScheduled(true);
            }

            foreach ($fixtureModel->getFixtures() as $fixture) {
                $output->write($fixture->getActionTitle() . '... ');
                $startTime = microtime(true);
                $fixture->execute();
                $endTime = microtime(true);
                $resultTime = $endTime - $startTime;
                $output->writeln(' done in ' . gmdate('H:i:s', $resultTime));
            }

            foreach ($indexerListIds as $indexerId) {
                /** @var $indexer \Magento\Indexer\Model\Indexer */
                $indexer = $indexerRegistry->get($indexerId['indexer_id']);
                $indexer->setScheduled($indexersState[$indexerId['indexer_id']]);
            }

            $fixtureModel->reindex($output);
            $totalEndTime = microtime(true);
            $totalResultTime = $totalEndTime - $totalStartTime;

            $output->writeln('<info>Total execution time: ' . gmdate('H:i:s', $totalResultTime) . '</info>');
        } catch (\Exception $e) {
             $output->writeln('<error>' . $e->getMessage() . '</error>');
        }
    }
}
