<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Riki\Customer\Plugin\Customer\Model;

use Magento\Customer\Api\CustomerRepositoryInterface as CustomerRepository;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Newsletter\Model\SubscriberFactory;
use Magento\Framework\Module\Manager;

class CustomerPlugin
{
    protected $_moduleManager;
    protected $subscriberFactory;

    public function __construct(
        SubscriberFactory $subscriberFactory,
        Manager $moduleManager
    ) {
        $this->subscriberFactory = $subscriberFactory;
        $this->_moduleManager = $moduleManager;
    }

    /**
     * Plugin after create customer that updates any newsletter subscription that may have existed.
     *
     * @param CustomerRepository $subject
     * @param CustomerInterface $result
     * @param CustomerInterface $customer
     * @return CustomerInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterSave(CustomerRepository $subject, CustomerInterface $result, CustomerInterface $customer)
    {
        if ($this->_moduleManager->isOutputEnabled('Magento_Newsletter')) {
            $this->subscriberFactory->create()->updateSubscription($result->getId());
        }

        return $result;
    }
}
