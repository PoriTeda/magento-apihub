<?php

namespace Riki\GoogleTagManager\Model;

/**
 * Class GaClient
 * @package Riki\GoogleTagManager\Model
 */
class GaClient implements \Riki\GoogleTagManager\Api\GaClientInterface
{

    /**
     * @var \Magento\Framework\Session\SessionManagerInterface
     */
    protected $_sessionManager;

    /**
     * GaClient constructor.
     * @param \Magento\Framework\Session\SessionManagerInterface $sessionManager
     */
    public function __construct(
        \Magento\Framework\Session\SessionManagerInterface $sessionManager
    )
    {
        $this->_sessionManager = $sessionManager;
    }

    /**
     * {@inheritdoc}
     */
    public function processGaClient($gaClientId)
    {
        if ($gaClientId != null) {
            $this->_sessionManager->setData('gaClientId', $gaClientId);
        }
    }


}