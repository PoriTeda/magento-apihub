<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCard\Model;

use Magento\Catalog\Api\Data\ProductOptionInterface;
use Magento\Catalog\Model\ProductOptionProcessorInterface;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\DataObject;
use Magento\Framework\DataObject\Factory as DataObjectFactory;
use Magento\GiftCard\Model\Giftcard\Option as GiftcardOption;
use Magento\GiftCard\Model\Giftcard\OptionFactory as GiftcardOptionFactory;

class ProductOptionProcessor implements ProductOptionProcessorInterface
{
    /**
     * @var DataObjectFactory
     */
    protected $objectFactory;

    /**
     * @var DataObjectHelper
     */
    protected $dataObjectHelper;

    /**
     * @var GiftcardOptionFactory
     */
    protected $giftCardOptionFactory;

    /**
     * @param DataObjectFactory $objectFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param GiftcardOptionFactory $giftCardOptionFactory
     */
    public function __construct(
        DataObjectFactory $objectFactory,
        DataObjectHelper $dataObjectHelper,
        GiftcardOptionFactory $giftCardOptionFactory
    ) {
        $this->objectFactory = $objectFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->giftCardOptionFactory = $giftCardOptionFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function convertToBuyRequest(ProductOptionInterface $productOption)
    {
        /** @var DataObject $request */
        $request = $this->objectFactory->create();

        $data = $this->getGiftcardItemOptionData($productOption);
        if (!empty($data)) {
            $request->addData($data);
        }

        return $request;
    }

    /**
     * Retrieve giftcard item option data
     *
     * @param ProductOptionInterface $productOption
     * @return array
     */
    protected function getGiftcardItemOptionData(ProductOptionInterface $productOption)
    {
        if ($productOption
            && $productOption->getExtensionAttributes()
            && $productOption->getExtensionAttributes()->getGiftcardItemOption()
        ) {
            return $productOption->getExtensionAttributes()
                ->getGiftcardItemOption()
                ->getData();
        }
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function convertToProductOption(DataObject $request)
    {
        $allowedOptions = [
            'giftcard_amount',
            'giftcard_sender_name',
            'giftcard_recipient_name',
            'giftcard_sender_email',
            'giftcard_recipient_email',
            'giftcard_message',
        ];

        $options = [];
        foreach ($allowedOptions as $optionKey) {
            $optionValue = $request->getData($optionKey);
            if ($optionValue) {
                $options[$optionKey] = $optionValue;
            }
        }

        if (!empty($options) && is_array($options)) {
            /** @var GiftcardOption $giftOption */
            $giftOption = $this->giftCardOptionFactory->create();
            $this->dataObjectHelper->populateWithArray(
                $giftOption,
                $options,
                \Magento\GiftCard\Api\Data\GiftCardOptionInterface::class
            );

            return ['giftcard_item_option' => $giftOption];
        };

        return [];
    }
}
