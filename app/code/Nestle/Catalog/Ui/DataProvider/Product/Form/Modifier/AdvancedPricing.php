<?php

namespace Nestle\Catalog\Ui\DataProvider\Product\Form\Modifier;

class AdvancedPricing extends \Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier
{
    /**
     * @var \Magento\Framework\Stdlib\ArrayManager
     */
    private $arrayManager;

    /**
     * @var array
     */
    protected $meta = [];

    public function __construct(\Magento\Framework\Stdlib\ArrayManager $arrayManager)
    {
        $this->arrayManager = $arrayManager;
    }


    /**
     * {@inheritdoc}
     */
    public function modifyMeta(array $meta)
    {
        $this->meta = $meta;

        $gpsPriceContainerPath = $this->arrayManager->findPath(
            'gps_price_ec',
            $this->meta,
            null,
            'children'
        );

        $advancedPriceButtonPath = $this->arrayManager->findPath(
            'advanced_pricing_button',
            $this->meta,
            null
        );

        // Move button node to container_gps_price_ec
        $this->meta = $this->arrayManager->move($advancedPriceButtonPath,
            $this->arrayManager->slicePath($gpsPriceContainerPath, 0, -1) . '/advanced_pricing_button',
            $this->meta
        );


        $this->meta = $this->arrayManager->merge(
            $this->arrayManager->findPath(
                static::CONTAINER_PREFIX . 'gps_price_ec',
                $this->meta,
                null,
                'children'
            ),
            $this->meta,
            [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'component' => 'Magento_Ui/js/form/components/group',
                        ],
                    ],
                ],
            ]
        );


        unset($this->meta[""]); // Remove error node

        return $this->meta;
    }

    /**
     * @param array $data
     * @return array
     * @since 100.1.0
     */
    public function modifyData(array $data)
    {
        return $data;
    }
}