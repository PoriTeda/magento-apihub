<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at https://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   mirasvit/module-fraud-check
 * @version   1.0.38
 * @copyright Copyright (C) 2019 Mirasvit (https://mirasvit.com/)
 */


namespace Mirasvit\FraudCheck\Console\Command;

use Magento\Framework\App\State;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Mirasvit\FraudCheck\Rule\Pool;
use Mirasvit\FraudCheck\Model\Context;
use Mirasvit\FraudCheck\Model\ScoreFactory;
use Mirasvit\FraudCheck\Model\RuleFactory;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;

class TestCommand extends Command
{
    /**
     * @var Pool
     */
    protected $pool;

    /**
     * @var Context
     */
    protected $context;

    /**
     * @var OrderCollectionFactory
     */
    protected $orderCollectionFactory;

    /**
     * @var ScoreFactory
     */
    protected $scoreFactory;

    /**
     * @var RuleFactory
     */
    protected $ruleFactory;

    /**
     * @param Pool                   $pool
     * @param Context                $context
     * @param ScoreFactory           $scoreFactory
     * @param RuleFactory            $ruleFactory
     * @param OrderCollectionFactory $orderCollectionFactory
     */
    public function __construct(
        Pool $pool,
        Context $context,
        ScoreFactory $scoreFactory,
        RuleFactory $ruleFactory,
        OrderCollectionFactory $orderCollectionFactory
    ) {
        $this->scoreFactory = $scoreFactory;
        $this->ruleFactory = $ruleFactory;
        $this->pool = $pool;
        $this->context = $context;
        $this->orderCollectionFactory = $orderCollectionFactory;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('mirasvit:fraud-check:test')
            ->setDescription('For test purpose.')
            ->setDefinition([]);

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        foreach ($this->orderCollectionFactory->create()->setPageSize(100) as $order) {
            $this->context->extractOrderData($order);

            $output->writeln('#' . $order->getIncrementId());

            $score = $this->scoreFactory->create();

            $score->setOrder($order);

            $output->writeln("<info>Fraud Risk Score: " . $score->getFraudScore() . "</info>");

            /** @var \Mirasvit\FraudCheck\Model\Rule $rule */
            foreach ($this->ruleFactory->create()->getCollection() as $rule) {
                $result = $rule->validate($order);
                $output->writeln("<info>Rule " . $rule->getName() . ": " . $result . "</info>");

            }

            //            foreach ($this->pool->getRules() as $rule) {
            //                $indicators = $rule->getIndicators();
            //
            //                foreach ($indicators as $indicator) {
            //                    $label = strip_tags($indicator->getLabel());
            //                    if ($indicator->isPositive()) {
            //                        $output->writeln("<info>$label</info>");
            //                    } else {
            //                        $output->writeln("<error>$label</error>");
            //                    }
            //                }
            //
            //            }
            //            $output->writeln('--------------');
        }
    }
}
