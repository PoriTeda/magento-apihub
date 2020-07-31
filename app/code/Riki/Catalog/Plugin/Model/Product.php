<?php
/**
 * Copyright © 2018 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\Catalog\Plugin\Model;

class Product
{
    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    protected $serializer;

    public function __construct(\Magento\Framework\Serialize\Serializer\Json $serializer = null)
    {
        $this->serializer = $serializer ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Magento\Framework\Serialize\Serializer\Json::class);
    }


    /**
     * Convert data for bundle product
     *
     * @param  \Magento\Catalog\Model\Product $subject
     * @param  $code
     * @param  $value
     * @param  $product
     * @return array
     * @throws \InvalidArgumentException
     */
    public function beforeAddCustomOption(
        \Magento\Catalog\Model\Product $subject,
        $code,
        $value,
        $product = null
    ) {

        if ($code == 'bundle_selection_ids') {
            $data = $this->serializer->unserialize($value);
            $value = $this->serializer->serialize(array_map('intval', $data));
        }

        return [
            $code,
            $value,
            $product
        ];
    }
}