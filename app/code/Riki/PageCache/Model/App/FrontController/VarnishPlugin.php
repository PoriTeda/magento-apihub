<?php

namespace Riki\PageCache\Model\App\FrontController;

use Magento\PageCache\Model\Config;
use Magento\Framework\App\PageCache\Version;
use Magento\Framework\App\State as AppState;
use Magento\Framework\App\FrontControllerInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\App\Response\Http as ResponseHttp;
use Magento\Framework\Controller\ResultInterface;

class VarnishPlugin extends \Magento\PageCache\Model\App\FrontController\VarnishPlugin
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
     * @var \Riki\PageCache\Helper\Varnish
     */
    protected $varnishHelper;

    /**
     * VarnishPlugin constructor.
     *
     * @param \Magento\PageCache\Model\Config $config
     * @param \Magento\Framework\App\PageCache\Version $version
     * @param \Magento\Framework\App\State $state
     * @param \Riki\PageCache\Helper\Varnish $varnishHelper
     */
    public function __construct(
        \Magento\PageCache\Model\Config $config,
        \Magento\Framework\App\PageCache\Version $version,
        \Magento\Framework\App\State $state,
        \Riki\PageCache\Helper\Varnish $varnishHelper
    ) {
        parent::__construct($config, $version, $state);
        $this->config = $config;
        $this->version = $version;
        $this->state = $state;
        $this->varnishHelper = $varnishHelper;
    }

    /**
     * Perform response postprocessing
     *
     * @param FrontControllerInterface $subject
     * @param ResponseInterface|ResultInterface $result
     * @return ResponseHttp|ResultInterface
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterDispatch(FrontControllerInterface $subject, $result)
    {
        if ($this->config->getType() == Config::VARNISH && $this->config->isEnabled()
            && $result instanceof ResponseHttp
        ) {
            $this->version->process();
            $this->varnishHelper->applyCacheControl($result);
            if ($this->state->getMode() == AppState::MODE_DEVELOPER) {
                $result->setHeader('X-Magento-Debug', 1);
            }
        }

        return $result;
    }

}
