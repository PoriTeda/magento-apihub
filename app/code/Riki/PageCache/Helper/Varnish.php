<?php

namespace Riki\PageCache\Helper;

class Varnish extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * Allow Varnish to cache but disable browser
     *
     * @param \Magento\Framework\App\Response\Http $response
     *
     * @return \Magento\Framework\App\Response\Http
     */
    public function applyCacheControl($response)
    {
        $cacheControl = $response->getHeader('cache-control');

        $maxAge = ($cacheControl && $cacheControl->hasDirective('max-age'))
            ? $cacheControl->getDirective('max-age') : null;

        if ($maxAge) {
            $response->setHeader(
                'cache-control',
                sprintf('no-store, no-cache, must-revalidate, max-age=%s', $maxAge),
                true
            );
        }
    }
}
