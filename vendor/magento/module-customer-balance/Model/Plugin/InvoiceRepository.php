<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerBalance\Model\Plugin;

use Magento\Sales\Api\Data\InvoiceInterface;

class InvoiceRepository
{
    /**
     * @var \Magento\Sales\Api\Data\InvoiceExtensionFactory
     */
    private $extensionFactory;

    /**
     * Init plugin
     *
     * @param \Magento\Sales\Api\Data\InvoiceExtensionFactory $invoiceExtensionFactory
     */
    public function __construct(
        \Magento\Sales\Api\Data\InvoiceExtensionFactory $invoiceExtensionFactory
    ) {
        $this->extensionFactory = $invoiceExtensionFactory;
    }

    /**
     * Get invoice customer balance
     *
     * @param \Magento\Sales\Api\InvoiceRepositoryInterface $subject
     * @param \Magento\Sales\Api\Data\InvoiceInterface $resultEntity
     * @return \Magento\Sales\Api\Data\InvoiceInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGet(
        \Magento\Sales\Api\InvoiceRepositoryInterface $subject,
        \Magento\Sales\Api\Data\InvoiceInterface $resultEntity
    ) {
        /** @var \Magento\Sales\Api\Data\InvoiceExtension $extensionAttributes */
        $extensionAttributes = $resultEntity->getExtensionAttributes();
        if ($extensionAttributes === null) {
            $extensionAttributes = $this->extensionFactory->create();
        }

        $extensionAttributes->setBaseCustomerBalanceAmount($resultEntity->getBaseCustomerBalanceAmount());
        $extensionAttributes->setCustomerBalanceAmount($resultEntity->getCustomerBalanceAmount());
        $resultEntity->setExtensionAttributes($extensionAttributes);

        return $resultEntity;
    }

    /**
     * @param \Magento\Sales\Api\InvoiceRepositoryInterface $subject
     * @param \Magento\Sales\Api\Data\InvoiceSearchResultInterface $resultInvoice
     * @return \Magento\Sales\Api\Data\InvoiceSearchResultInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetList(
        \Magento\Sales\Api\InvoiceRepositoryInterface $subject,
        \Magento\Sales\Api\Data\InvoiceSearchResultInterface $resultInvoice
    ) {
        /** @var InvoiceInterface $invoice */
        foreach ($resultInvoice->getItems() as $invoice) {
            $this->afterGet($subject, $invoice);
        }
        return $resultInvoice;
    }

    /**
     * Add customer balance amount information to invoice
     *
     * @param \Magento\Sales\Api\InvoiceRepositoryInterface $subject
     * @param \Magento\Sales\Api\Data\InvoiceInterface $entity
     *
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeSave(
        \Magento\Sales\Api\InvoiceRepositoryInterface $subject,
        \Magento\Sales\Api\Data\InvoiceInterface $entity
    ) {
        $extensionAttributes = $entity->getExtensionAttributes();
        if ($extensionAttributes) {
            $entity->setCustomerBalanceAmount($extensionAttributes->getCustomerBalanceAmount());
            $entity->setBaseCustomerBalanceAmount($extensionAttributes->getBaseCustomerBalanceAmount());
        }
    }
}
