<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCardAccount\Model\Plugin;

/**
 * Plugin for Invoice repository.
 */
class InvoiceRepository
{
    /**
     * @var \Magento\Sales\Api\Data\InvoiceExtensionFactory
     */
    private $extensionFactory;

    /**
     * @param \Magento\Sales\Api\Data\InvoiceExtensionFactory $extensionFactory
     */
    public function __construct(
        \Magento\Sales\Api\Data\InvoiceExtensionFactory $extensionFactory
    ) {
        $this->extensionFactory = $extensionFactory;
    }

    /**
     * @param \Magento\Sales\Api\InvoiceRepositoryInterface $subject
     * @param \Magento\Sales\Api\Data\InvoiceInterface $entity
     *
     * @return \Magento\Sales\Api\Data\InvoiceInterface
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGet(
        \Magento\Sales\Api\InvoiceRepositoryInterface $subject,
        \Magento\Sales\Api\Data\InvoiceInterface $entity
    ) {
        /** @var \Magento\Sales\Api\Data\InvoiceExtension $extensionAttributes */
        $extensionAttributes = $entity->getExtensionAttributes();

        if ($extensionAttributes === null) {
            $extensionAttributes = $this->extensionFactory->create();
        }

        $extensionAttributes->setBaseGiftCardsAmount($entity->getBaseGiftCardsAmount());
        $extensionAttributes->setGiftCardsAmount($entity->getGiftCardsAmount());

        $entity->setExtensionAttributes($extensionAttributes);

        return $entity;
    }

    /**
     * @param \Magento\Sales\Api\InvoiceRepositoryInterface $subject
     * @param \Magento\Sales\Api\Data\InvoiceSearchResultInterface $entities
     *
     * @return \Magento\Sales\Api\Data\InvoiceSearchResultInterface
     */
    public function afterGetList(
        \Magento\Sales\Api\InvoiceRepositoryInterface $subject,
        \Magento\Sales\Api\Data\InvoiceSearchResultInterface $entities
    ) {
        /** @var \Magento\Sales\Api\Data\InvoiceInterface $entity */
        foreach ($entities->getItems() as $entity) {
            $this->afterGet($subject, $entity);
        }

        return $entities;
    }

    /**
     * Sets gift card account data from extension attributes
     * to Invoice model.
     *
     * @param \Magento\Sales\Api\InvoiceRepositoryInterface $subject
     * @param \Magento\Sales\Api\Data\InvoiceInterface $entity
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeSave(
        \Magento\Sales\Api\InvoiceRepositoryInterface $subject,
        \Magento\Sales\Api\Data\InvoiceInterface $entity
    ) {
        $extensionAttributes = $entity->getExtensionAttributes();

        if ($extensionAttributes) {
            $entity->setGiftCardsAmount($extensionAttributes->getGiftCardsAmount());
            $entity->setBaseGiftCardsAmount($extensionAttributes->getBaseGiftCardsAmount());
        }
    }
}
