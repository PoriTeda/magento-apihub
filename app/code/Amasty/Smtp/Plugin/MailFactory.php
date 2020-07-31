<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2019 Amasty (https://www.amasty.com)
 * @package Amasty_Smtp
 */


namespace Amasty\Smtp\Plugin;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Mail\TransportInterfaceFactory;
use Magento\Framework\ObjectManagerInterface;

class MailFactory {

    protected $_instanceName = null;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * Object Manager instance
     *
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager = null;
    /**
     * @var \Amasty\Smtp\Helper\Data
     */
    protected $helper;

    /**
     * @var \Amasty\Smtp\Model\Config
     */
    private $config;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ObjectManagerInterface $objectManager,
        \Amasty\Smtp\Helper\Data $helper,
        \Amasty\Smtp\Model\Config $config,
        $instanceName = \Amasty\Smtp\Model\Transport::class
    ) {
        $this->_instanceName = $instanceName;
        $this->scopeConfig = $scopeConfig;
        $this->_objectManager = $objectManager;
        $this->helper = $helper;
        $this->config = $config;
    }

    /**
     * @param TransportInterfaceFactory $subject
     * @param \Closure $proceed
     * @param array $data
     * @return mixed
     */
    public function aroundCreate(
        TransportInterfaceFactory $subject,
        \Closure $proceed,
        array $data = []
    ) {
        $storeId = $this->helper->getCurrentStore();

        if ($this->config->isSmtpEnable($storeId)) {
            $data = array_merge($data, $this->config->getSmtpConfig($storeId));
            return $this->_objectManager->create($this->_instanceName, $data);
        } else {
            return $proceed($data);
        }
    }
}
