<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2019 Amasty (https://www.amasty.com)
 * @package Amasty_Smtp
 */


namespace Amasty\Smtp\Model\Provider\Config;

use Magento\Framework\Config\ConverterInterface;

class Converter implements ConverterInterface
{
    /**
     * Convert dom node tree to array
     *
     * @param mixed $source
     * @return array
     */
    public function convert($source)
    {
        $providers = [];

        $providerNodes = $source->getElementsByTagName('provider');
        /** @var $providerNode \DOMElement */
        foreach ($providerNodes as $providerNode) {
            $provider = [];

            /** @var $childNode \DOMElement */
            foreach ($providerNode->childNodes as $childNode) {
                if ($childNode instanceof \DOMElement) {
                    $provider[$childNode->tagName] = $childNode->nodeValue;
                }
            }

            $providers []= $provider;
        }

        return $providers;
    }
}
