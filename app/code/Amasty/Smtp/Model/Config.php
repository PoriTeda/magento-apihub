<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2019 Amasty (https://www.amasty.com)
 * @package Amasty_Smtp
 */


namespace Amasty\Smtp\Model;

use Magento\Store\Model\ScopeInterface;
use \Magento\Framework\App\Config\ScopeConfigInterface;

class Config
{
    const CONFIG_PATH_GENERAL_CONFIG = 'amsmtp/general/';
    const CONFIG_PATH_SMTP_CONFIG = 'amsmtp/smtp';

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var \Magento\Config\Model\Config\Backend\Encrypted
     */
    private $encrypted;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        \Magento\Config\Model\Config\Backend\Encrypted $encrypted
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->encrypted = $encrypted;
    }

    /**
     * @param $path
     * @param null $storeId
     * @param string $scope
     * @return mixed
     */
    public function getConfigValueByPath(
        $path,
        $storeId = null,
        $scope = ScopeInterface::SCOPE_STORE
    ) {
        return $this->scopeConfig->getValue($path, $scope, $storeId);
    }

    /**
     * @param int|string $storeId
     * @return mixed
     */
    public function isSmtpEnable($storeId)
    {
        return $this->scopeConfig->isSetFlag(
            self::CONFIG_PATH_GENERAL_CONFIG . 'enable', ScopeInterface::SCOPE_STORE, $storeId
        );
    }

    /**
     * @param $storeId
     * @param string $scope
     *
     * @return array
     */
    public function getSmtpConfig($storeId, $scope = ScopeInterface::SCOPE_STORE)
    {
        $result = [];
        $config = $this->getConfigValueByPath(self::CONFIG_PATH_SMTP_CONFIG, $storeId, $scope);
        $result['host'] = $config['server'];

        $result['provider'] = $config['provider'];
        $result['provider'] = $config['provider'];
        $result['parameters'] = [
            'port' => $config['port'],
            'auth' => $config['auth'],
            'ssl'  => $config['sec'],
        ];

        if (isset($config['login'], $config['passw'])) {
            $result['parameters']['username'] = trim($config['login']);
            $result['parameters']['password'] = $this->encrypted->processValue($config['passw']);
        }

        if ($config['provider'] == 0
            && isset($config['use_custom_email_sender'])
            && $config['use_custom_email_sender']
        ) {
            $result['parameters']['custom_sender'] = [
                'email' => $config['custom_sender_email'],
                'name' => $config['custom_sender_name'],
            ];
        }

        if (!$result['parameters']['ssl']) {
            unset($result['parameters']['ssl']);
        }

        $result['test_email'] = $config['test_email'];

        return $result;
    }

    /**
     * @param $storeId
     * @param string $scope
     *
     * @return mixed
     */
    public function getGeneralEmail($storeId, $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT)
    {
        return $this->scopeConfig->getValue('trans_email/ident_general', $scope, $storeId);
    }
}
