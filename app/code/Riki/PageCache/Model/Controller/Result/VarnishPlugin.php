<?php

namespace Riki\PageCache\Model\Controller\Result;

use Magento\Framework\App\Response\Http as ResponseHttp;
use Magento\PageCache\Model\Config;
use Magento\Framework\App\PageCache\Version;
use Magento\Framework\App\State as AppState;
use Magento\Framework\Registry;
use Magento\Framework\Controller\ResultInterface;


class VarnishPlugin extends \Magento\PageCache\Model\Controller\Result\VarnishPlugin
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var Version
     */
    private $version;

    /**
     * @var AppState
     */
    private $state;

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var \Riki\PageCache\Helper\Varnish
     */
    private $varnishHelper;


    public function __construct(
        Config $config,
        Version $version,
        AppState $state,
        Registry $registry,
        \Riki\PageCache\Helper\Varnish $varnishHelper
    ) {
        parent::__construct($config, $version, $state, $registry);
        $this->config = $config;
        $this->version = $version;
        $this->state = $state;
        $this->registry = $registry;
        $this->varnishHelper = $varnishHelper;
    }

    public function afterRenderResult(ResultInterface $subject, ResultInterface $result, ResponseHttp $response)
    {
        $usePlugin = $this->registry->registry('use_page_cache_plugin');

        if ($this->config->getType() == Config::VARNISH && $this->config->isEnabled() && $usePlugin) {
            $this->version->process();
            $this->varnishHelper->applyCacheControl($response);
            if ($this->state->getMode() == AppState::MODE_DEVELOPER) {
                $response->setHeader('X-Magento-Debug', 1);
            }
        }

        return $result;
    }
}
