<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/** @var \Magento\Framework\Registry $registry */
$registry = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->get(\Magento\Framework\Registry::class);
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

/** @var \Magento\Customer\Model\Customer $customer */
$customer = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()
    ->create(\Magento\Customer\Model\Customer::class);
$customer->setWebsiteId(1);
$customer->loadByEmail('CharlesTAlston@teleworm.us');
$customer->delete();

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);

require 'customer_custom_attribute_rollback.php';
