<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCardAccount\Model\Plugin;

/**
 * Plugin for Creditmemo repository.
 */
class CreditmemoRepository
{
    /**
     * @var \Magento\Sales\Api\Data\CreditmemoExtensionFactory
     */
    private $extensionFactory;

    /**
     * @param \Magento\Sales\Api\Data\CreditmemoExtensionFactory $extensionFactory
     */
    public function __construct(\Magento\Sales\Api\Data\CreditmemoExtensionFactory $extensionFactory)
    {
        $this->extensionFactory = $extensionFactory;
    }

    /**
     * @param \Magento\Sales\Api\CreditmemoRepositoryInterface $subject
     * @param \Magento\Sales\Api\Data\CreditmemoInterface $entity
     *
     * @return \Magento\Sales\Api\Data\CreditmemoInterface
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGet(
        \Magento\Sales\Api\CreditmemoRepositoryInterface $subject,
        \Magento\Sales\Api\Data\CreditmemoInterface $entity
    ) {
        /** @var \Magento\Sales\Api\Data\CreditmemoExtension $extensionAttributes */
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
     * @param \Magento\Sales\Api\CreditmemoRepositoryInterface $subject
     * @param \Magento\Sales\Api\Data\CreditmemoSearchResultInterface $entities
     *
     * @return \Magento\Sales\Api\Data\CreditmemoSearchResultInterface
     */
    public function afterGetList(
        \Magento\Sales\Api\CreditmemoRepositoryInterface $subject,
        \Magento\Sales\Api\Data\CreditmemoSearchResultInterface $entities
    ) {
        /** @var \Magento\Sales\Api\Data\CreditmemoInterface $entity */
        foreach ($entities->getItems() as $entity) {
            $this->afterGet($subject, $entity);
        }

        return $entities;
    }
}
