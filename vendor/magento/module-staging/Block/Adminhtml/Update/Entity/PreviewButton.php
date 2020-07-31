<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Staging\Block\Adminhtml\Update\Entity;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use Magento\Staging\Block\Adminhtml\Update\IdProvider as UpdateIdProvider;
use Magento\Staging\Model\Preview\UrlBuilder;

/**
 * Class PreviewButton
 */
class PreviewButton implements ButtonProviderInterface
{
    /**
     * @var EntityProviderInterface
     */
    protected $entityProvider;

    /**
     * @var UpdateIdProvider
     */
    protected $updateIdProvider;

    /**
     * @var UrlBuilder
     */
    protected $previewUrlBuilder;

    /**
     * @param EntityProviderInterface $entityProvider
     * @param UpdateIdProvider $updateIdProvider
     * @param UrlBuilder $previewUrlBuilder
     */
    public function __construct(
        EntityProviderInterface $entityProvider,
        UpdateIdProvider $updateIdProvider,
        UrlBuilder $previewUrlBuilder
    ) {
        $this->entityProvider = $entityProvider;
        $this->updateIdProvider = $updateIdProvider;
        $this->previewUrlBuilder = $previewUrlBuilder;
    }

    /**
     * @return array
     */
    public function getButtonData()
    {
        $data = [];
        if ($this->updateIdProvider->getUpdateId()) {
            $data = [
                'label' => __('Preview'),
                'url' => $this->previewUrlBuilder->getPreviewUrl(
                    $this->updateIdProvider->getUpdateId(),
                    $this->entityProvider->getUrl($this->updateIdProvider->getUpdateId())
                ),
                'sort_order' => 20,
            ];
        }
        return $data;
    }
}
