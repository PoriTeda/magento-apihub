<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2019 Amasty (https://www.amasty.com)
 * @package Amasty_Smtp
 */


namespace Amasty\Smtp\Controller\Adminhtml\Config;

use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;


class Check extends \Magento\Config\Controller\Adminhtml\System\Config\Save
{
    const AMSMTP_SECTION_NAME = 'amsmtp';
    const CONFIG_PATH_SMTP_PASWORD_CONFIG = 'amsmtp/smtp/passw';

    /**
     * @var \Magento\Config\Model\Config\Backend\Encrypted
     */
    private $encrypted;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var \Amasty\Smtp\Model\Config
     */
    private $config;

    /**
     * @var \Amasty\Smtp\Helper\Data
     */
    private $helper;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Config\Model\Config\Structure $configStructure,
        \Magento\Config\Controller\Adminhtml\System\ConfigSectionChecker $sectionChecker,
        \Magento\Config\Model\Config\Factory $configFactory,
        \Magento\Framework\Cache\FrontendInterface $cache,
        \Magento\Framework\Stdlib\StringUtils $string,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Config\Model\Config\Backend\Encrypted $encrypted,
        \Amasty\Smtp\Model\Config $config,
        \Amasty\Smtp\Helper\Data $helper
    ) {
        parent::__construct(
            $context,
            $configStructure,
            $sectionChecker,
            $configFactory,
            $cache,
            $string
        );
        $this->scopeConfig = $scopeConfig;
        $this->encrypted = $encrypted;
        $this->config = $config;
        $this->helper = $helper;
    }

    /**
     * @return $this
     */
    public function execute()
    {
        try {
            $configData = [
                'section' => self::AMSMTP_SECTION_NAME,
                'website' => $this->getRequest()->getParam('website'),
                'store' => $this->getRequest()->getParam('store'),
                'groups' => $this->_getGroupsForSave(),
            ];

            /** @var \Magento\Config\Model\Config $configModel  */
            $configModel = $this->_configFactory->create(['data' => $configData]);
            $configModel->save();

            $storeId = $configData['store'] ?: $configData['website'];
            $scope = $configData['store'] ? ScopeInterface::SCOPE_STORE : ScopeInterface::SCOPE_WEBSITE;

            if (!$configData['store'] && !$configData['website']) {
                $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
                $smtpConfigData = $this->config->getSmtpConfig($this->helper->getCurrentStore());
            } else {
                $smtpConfigData = $this->config->getSmtpConfig($storeId, $scope);
            }

            $transport = $this->_objectManager->create(
                \Amasty\Smtp\Model\Transport::class,
                $smtpConfigData
            );

            $transport->runTest($smtpConfigData['test_email'], $storeId, $scope);
            $this->messageManager->addSuccessMessage(__('Connection Successful!'));
        } catch (LocalizedException $e) {
            $this->messageManager->addNoticeMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addNoticeMessage($e->getMessage());
        }

        $resultRedirect = $this->resultRedirectFactory->create();

        return $resultRedirect->setRefererUrl();
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Amasty_Smtp::config');
    }
}
