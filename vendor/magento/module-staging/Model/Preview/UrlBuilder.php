<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Staging\Model\Preview;

use Magento\Framework\Url;
use Magento\Framework\UrlInterface;
use Magento\Staging\Model\VersionManager;

class UrlBuilder
{
    /**#@+
     * Get parameters names.
     */
    const PARAM_PREVIEW_URL = 'preview_url';
    const PARAM_PREVIEW_STORE = 'preview_store';
    const PARAM_PREVIEW_VERSION = 'preview_version';
    /**#@-*/

    /**
     * Preview url
     */
    const URL_PATH_PREVIEW = 'staging/update/preview';

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var Url
     */
    protected $frontendUrl;

    /**
     * @param UrlInterface $urlBuilder
     * @param Url $frontendUrl
     */
    public function __construct(
        UrlInterface $urlBuilder,
        Url $frontendUrl
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->frontendUrl = $frontendUrl;
    }

    /**
     * Return staging preview URL for version and url
     *
     * @param int $versionId
     * @param string|null $url
     * @return string
     */
    public function getPreviewUrl($versionId, $url = null)
    {
        return $this->urlBuilder->getUrl(
            self::URL_PATH_PREVIEW,
            [
                '_query' => [
                    self::PARAM_PREVIEW_VERSION => $versionId,
                    self::PARAM_PREVIEW_URL => $url !== null ? $url : $this->frontendUrl->getUrl()
                ],
            ]
        );
    }
}
