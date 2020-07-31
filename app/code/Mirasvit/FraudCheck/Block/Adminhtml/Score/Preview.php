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


namespace Mirasvit\FraudCheck\Block\Adminhtml\Score;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Mirasvit\FraudCheck\Model\Score;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;

/**
 * @method $this setScore($score)
 * @method Score getScore()
 */
class Preview extends Template
{
    /**
     * @var string
     */
    protected $_template = 'score/preview.phtml';

    /**
     * @var OrderCollectionFactory
     */
    protected $orderCollectionFactory;

    /**
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param Context                $context
     */
    public function __construct(
        OrderCollectionFactory $orderCollectionFactory,
        Context $context
    ) {

        $this->orderCollectionFactory = $orderCollectionFactory;

        parent::__construct($context);
    }

    /**
     * @return \Magento\Sales\Model\Order[]
     */
    public function getOrders()
    {
        return $this->orderCollectionFactory->create()
            ->setPageSize(10);
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return float
     */
    public function getFraudScore($order)
    {
        $this->getScore()->setOrder($order);

        return $this->getScore()->getFraudScore(true, false);
    }
}
