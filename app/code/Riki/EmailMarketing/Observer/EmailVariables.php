<?php
/**
 * PHP version 7
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 *
 * @category  Riki_EmailMarketing
 * @package   Riki\EmailMarketing\Observer
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Riki\EmailMarketing\Observer;
use Magento\Framework\Event\ObserverInterface;
use Riki\EmailMarketing\Helper\Order as OrderHelper;
/**
 * Class EmailVariables
 *
 * @category  Riki_EmailMarketing
 * @package   Riki\EmailMarketing\Observer
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class EmailVariables implements ObserverInterface
{
    /**
     * @var OrderHelper
     */
    protected $orderHelper;

    /**
     * EmailVariables constructor.
     * @param OrderHelper $orderHelper
     */
    public function __construct(
       OrderHelper $orderHelper
    ) {
        $this->orderHelper = $orderHelper;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $transport = $observer->getTransport();
        $order = $transport['order'];
        if($order)
        {
            $variables = $this->orderHelper->getOrderVariables($order);
            $transport = array_merge($transport,$variables);
        }
    }
}