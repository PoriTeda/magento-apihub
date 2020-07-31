<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Eway\Gateway\Http;

use Magento\Eway\Gateway\Helper\Request\Action;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Http\TransferBuilder;
use Magento\Payment\Gateway\Http\TransferFactoryInterface;

/**
 * Class AbstractTransferFactory
 */
abstract class AbstractTransferFactory implements TransferFactoryInterface
{
    /**
     * @var ConfigInterface
     */
    protected $config;

    /**
     * @var TransferBuilder
     */
    protected $transferBuilder;

    /**
     * @var Action
     */
    protected $action;

    /**
     * @param ConfigInterface $config
     * @param TransferBuilder $transferBuilder
     * @param Action $action
     */
    public function __construct(
        ConfigInterface $config,
        TransferBuilder $transferBuilder,
        Action $action
    ) {
        $this->config = $config;
        $this->transferBuilder = $transferBuilder;
        $this->action = $action;
    }

    /**
     * @return string
     */
    protected function getApiKey()
    {
        return (bool) $this->config->getValue('sandbox_flag')
            ? $this->config->getValue('sandbox_api_key')
            : $this->config->getValue('live_api_key');
    }

    /**
     * @return string
     */
    protected function getApiPassword()
    {
        return (bool) $this->config->getValue('sandbox_flag')
            ? $this->config->getValue('sandbox_api_password')
            : $this->config->getValue('live_api_password');
    }
}
