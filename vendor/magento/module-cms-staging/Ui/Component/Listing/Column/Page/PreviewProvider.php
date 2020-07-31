<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CmsStaging\Ui\Component\Listing\Column\Page;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Staging\Model\Preview\UrlBuilder;
use Magento\Staging\Model\VersionManager;
use Magento\Staging\Ui\Component\Listing\Column\Entity\UrlProviderInterface;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Class PreviewProvider
 */
class PreviewProvider implements UrlProviderInterface
{
    /**
     * @var UrlInterface
     */
    protected $frontendUrlBuilder;

    /**
     * @param UrlInterface $frontendUrlBuilder
     */
    public function __construct(
        UrlInterface $frontendUrlBuilder
    ) {
        $this->frontendUrlBuilder = $frontendUrlBuilder;
    }

    /**
     * Get CMS page URL for data provider item
     *
     * @param array $item
     * @return string
     */
    public function getUrl(array $item)
    {
        return $this->frontendUrlBuilder->getUrl(
            null,
            [
                '_direct' => $item['identifier'],
                '_nosid' => true,
            ]
        );
    }
}
