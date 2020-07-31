<?php

namespace Wyomind\Core\Block;

class Webservice extends \Magento\Framework\View\Element\Template
{

    protected $_coreHelper = null;
    protected $_cacheManager = null;
    protected $_session = null;
    protected $_message = "";

    /**
     *
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Catalog\Block\Product\Context $pcontext
     * @param \Magento\Catalog\Model\Product $productModel
     * @param array $data
     * @ignore_var product
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Model\Context $contextBis,
        \Wyomind\Core\Helper\Data $coreHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_coreHelper = $coreHelper;
        $this->_cacheManager = $contextBis->getCacheManager();
        $this->_session = $context->getSession();
        if ($this->getRequest()->getParam("namespace")) {
            $namespace = $this->getRequest()->getParam("namespace");
            $wgsActivationKey = $this->getRequest()->getParam("wgs_activation_key");
            $wgsStatus = $this->getRequest()->getParam("wgs_status");
            $wgsVersion = $this->getRequest()->getParam("wgs_version");
            $wgsActivation = $this->getRequest()->getParam("wgs_activation");
            $wgsMessage = $this->getRequest()->getParam("wgs_message");

            $activationKey = $this->_coreHelper->getDefaultConfigUncrypted("$namespace/license/activation_key");
            $baseUrl = str_replace("{{unsecure_base_url}}", $this->_coreHelper->getDefaultConfig("web/unsecure/base_url"), $this->_coreHelper->getDefaultConfig("web/secure/base_url"));
            $registeredVersion = $this->_coreHelper->getDefaultConfig("$namespace/license/version");
        } else {
            $this->_message = "<div class='message message-error error'>" . __("Invalid data.") . "</div>";
        }


        if (isset($wgsActivationKey) && $wgsActivationKey == $this->_coreHelper->getStoreConfigUncrypted("$namespace/license/activation_key")) {
            if (isset($wgsStatus)) {
                switch ($wgsStatus) {
                    case "success":
                        $this->_coreHelper->setDefaultConfig("$namespace/license/version", $wgsVersion);
                        $this->_coreHelper->setDefaultConfig("$namespace/license/activation_flag", 0);
                        $this->_coreHelper->setDefaultConfigCrypted("$namespace/license/activation_code", $wgsActivation);
                        $this->_coreHelper->setDefaultConfig('advanced/modules_disable_output/Wyomind_' . ucfirst($namespace), 0);
                        $this->_session->setData("update_" . $namespace, "false");
                        $this->_cacheManager->clean(['config']);
                        $this->_message = "<div class='message message-success success'>" . $wgsMessage . "</div>";
                        break;
                    case "error":
                        $this->_message = "<div class='message message-success success'>" . $wgsMessage . "</div>";
                        $this->_coreHelper->setDefaultConfig('advanced/modules_disable_output/Wyomind_' . ucfirst($namespace), 1);
                        $this->_coreHelper->setDefaultConfig("$namespace/license/activation_code", "");
                        $this->_cacheManager->clean(['config']);
                        break;
                    case "uninstall":
                        $this->_message = "<div class='message message-success success'>" . $wgsMessage . "</div>";
                        $this->setDefaultConfig("$namespace/license/activation_key", "");
                        $this->setDefaultConfig("$namespace/license/activation_code", "");
                        $this->setStoreConfig('advanced/modules_disable_output/Wyomind_' . ucfirst($namespace), 1);
                        $this->_cacheManager->clean(['config']);
                        $this->getResponse()->setBody(
                            "
                            <form action='http://www.wyomind.com/license_activation/?method=post' id='license_uninstall' method='post'>
                                <input type='hidden' type='action' value='uninstall' name='action'>
                                <input type='hidden' value='" . $baseUrl . "' name='domain'>
                                <input type='hidden' value='" . $activationKey . "' name='activation_key'>
                                <input type='hidden' value='" . $registeredVersion . "' name='registered_version'>
                                <button type='submit'" . __("If nothing happens click here !") . "</button>
                                <script language='javascript'>
                                        document.getElementById('license_uninstall').submit();
                                </script>
                            </form>"
                        );
                        break;
                    default:
                        $this->_message = __("An error occurs while retrieving the license activation (500)");
                        $this->_coreHelper->setDefaultConfig("$namespace/license/activation_code", "");
                        $this->_coreHelper->setDefaultConfig('advanced/modules_disable_output/Wyomind_' . ucfirst($namespace), 1);
                        $this->_cacheManager->clean(['config']);
                        break;
                }
            } else {
                $this->_message = "<div class='message message-error error'>" . __("An error occurs while retrieving license activation (404).") . "</div>";
            }
        } else {
            $this->_message = "<div class='message message-error error'>" . __("Invalid activation key.") . "</div>";
        }
    }

    public function getMessage()
    {
        return $this->_message;
    }
}
