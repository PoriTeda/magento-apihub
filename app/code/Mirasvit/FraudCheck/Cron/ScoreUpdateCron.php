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



namespace Mirasvit\FraudCheck\Cron;

use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Mirasvit\FraudCheck\Model\ScoreFactory;

class ScoreUpdateCron
{
    /**
     * @var OrderCollectionFactory
     */
    private $orderCollectionFactory;

    /**
     * @var ScoreFactory
     */
    private $scoreFactory;

    public function __construct(
        OrderCollectionFactory $orderCollectionFactory,
        ScoreFactory $scoreFactory
    ) {
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->scoreFactory = $scoreFactory;
    }

    /**
     * @return void
     */
    public function execute()
    {
        $collection = $this->orderCollectionFactory->create();

        $collection->addFieldToFilter('fraud_score', ['null' => true])
            ->setPageSize(100)
            ->setOrder('created_at', 'desc');

        foreach ($collection as $order) {
            echo 'OrderID: ' . $order->getId() . ' ' . $order->getFraudScore() . PHP_EOL;
            if (!$order->getPayment()) {
                continue;
            }

            $score = $this->scoreFactory->create();
            $score->setOrder($order)
                ->getFraudScore();

            // for update status
            $order = $order->load($order->getId());
            $score->setOrder($order);
        }
    }
}