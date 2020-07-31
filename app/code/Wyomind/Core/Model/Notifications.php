<?php

/**
 * Copyright © 2015 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Wyomind\Core\Model;

/**
 * License Notifications
 */
class Notifications extends \Magento\AdminNotification\Model\System\Message
{

    const SOAP_URL = "https://www.wyomind.com/services/licenses/webservice.soap.php";
    const SOAP_URI = "https://www.wyomind.com/";
    const WS_URL = "https://www.wyomind.com/license_activation/?licensemanager=%s&";

    protected $_values = [];
    protected $_version = "3.0.7";
    protected $_warnings = [];
    protected $_coreHelper = null;
    protected $_cacheManager = null;
    protected $_directoryRead = [];
    protected $_directoryList = null;
    protected $_refreshCache = false;
    protected $_messages = [
        "activation_key_warning" => "Your activation key is not yet registered.<br>Go to <a href='%s'>Stores > Configuration > Wyomind > %s</a>.",
        "license_code_warning" => "Your license is not yet activated.<br><a target='_blank' href='%s'>Activate it now !</a>",
        "license_code_updated_warning" => "Your license must be re-activated.<br><a target='_blank' href='%s'>Re-activate it now !</a>",
        "ws_error" => "The Wyomind's license server encountered an error.<br><a target='_blank' href='%s'>Please go to Wyomind license manager</a>",
        "ws_success" => "<b style='color:green'>%s</b>",
        "ws_failure" => "<b style='color:red'>%s</b>",
        "ws_no_allowed" => "Your server doesn't allow remote connections.<br><a target='_blank' href='%s'>Please go to Wyomind license manager</a>",
        "upgrade" => "<u>Extension upgrade from v%s to v%s</u>.<br> Your license must be updated.<br>Please clean all caches and reload this page.",
        "license_warning" => "License Notification",
        "flag" => "Your license has been deactivated because of wrong activation key or license code.<br>Go to <a href='%s'>Stores > Configuration > Wyomind > %s</a>."
    ];
    protected $_magentoVersion = 0;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Module\ModuleList $moduleList,
        \Magento\Framework\App\Config\MutableScopeConfigInterface $scopeConfig,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Framework\Session\SessionManagerInterface $session,
        \Wyomind\Core\Helper\Data $coreHelper,
        \Magento\Framework\Filesystem\Directory\ReadFactory $directoryRead,
        \Magento\Framework\Filesystem\File\ReadFactory $fileRead,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {

        parent::__construct($context, $registry, $resource, $resourceCollection, $data);

        
        $this->_magentoVersion = $coreHelper->getMagentoVersion();

        $this->_moduleList = $moduleList;
        $this->_scopeConfig = $scopeConfig;
        $this->_urlBuilder = $urlBuilder;
        $this->_cacheManager = $context->getCacheManager();
        $this->_session = $session;
        $this->_coreHelper = $coreHelper;
        $root = $directoryList->getPath(\Magento\Framework\App\Filesystem\DirectoryList::ROOT);
        if (file_exists($root . "/vendor/wyomind/")) {
            $this->_directoryRead[] = $directoryRead->create($root . "/vendor/wyomind/");
        }
        if (file_exists($root . "/app/code/Wyomind/")) {
            $this->_directoryRead[] = $directoryRead->create($root . "/app/code/Wyomind/");
        }
        $this->_httpRead = $fileRead;
        $this->_directoryList = $directoryList;

        $this->_version = $this->_moduleList->getOne("Wyomind_Core")['setup_version'];

        $this->_refreshCache = false;

        $this->getValues();
        foreach ($this->_values as $ext) {
            $this->checkActivation($ext);
        }

        if ($this->_refreshCache) {
            $this->_cacheManager->clean(['config']);
        }
    }
    
    public function isModuleEnabled($module)
    {
        $module = strtolower($module);
        $list = $this->_moduleList->getNames();
        foreach ($list as $mod) {
            if (strtolower($mod) == "wyomind_".$module) {
                return true;
            }
        }
        return false;
    }

    /**
     * Retrieve extensions information
     */
    public function getValues()
    {
        $dir = ".";
        $ret = [];
        foreach ($this->_directoryRead as $directoryRead) {
            foreach ($directoryRead->read($dir) as $file) {
                if ($directoryRead->isDirectory($file) && $file != "." && $file != "..") {
                    if ($directoryRead->isFile($file . "/etc/config.xml")) {
                        $namespace = strtolower(str_replace("./", "", $file));
                        if ($this->isModuleEnabled(str_replace("./", "", $file))) { // disabled ?
                            $label = $this->_coreHelper->getStoreConfig($namespace . "/license/extension_label");
                            $version = $this->_coreHelper->getStoreConfig($namespace . "/license/extension_version");
                            $ret[] = ["label" => $label, "value" => $file, "version" => $version];
                        }
                    }
                }
            }
        }
        $this->_values = $ret;
    }

    /**
     * Transform XML to array
     * @param string $xml
     * @return array
     */
    public function XML2Array($xml)
    {
        $newArray = [];
        $array = (array) $xml;
        foreach ($array as $key => $value) {
            $value = (array) $value;
            if (isset($value [0])) {
                $newArray [$key] = trim($value [0]);
            } else {
                $newArray [$key] = $this->XML2Array($value, true);
            }
        }
        return $newArray;
    }

    /**
     * Add a license warning
     * @param type $name
     * @param type $type
     * @param type $vars
     */
    protected function addWarning(
        $name,
        $type,
        $vars = []
    ) {

        if ($type) {
            $output = $this->sprintfArray($this->_messages[$type], $vars);
        } else {
            $output = implode(" " . $vars);
        }
        $output = "<b> Wyomind " . $name . "</b> <br> " . $output . "";

        $this->_warnings[] = $output;
    }

    /**
     * Print array
     * @param string $format
     * @param array  $arr
     * @return string
     */
    protected function sprintfArray(
        $format,
        $arr
    ) {
        return call_user_func_array("sprintf", array_merge((array) $format, $arr));
    }

    /**
     * Check if extension can be registered
     * @param string $extension
     */
    protected function checkActivation($extension)
    {
        $wsUrl = sprintf(self::WS_URL, $this->_version);

        $ext = "" . strtolower(str_replace("./", "", $extension["value"]));

        $activationKey = $this->_coreHelper->getDefaultConfigUncrypted($ext . "/license/activation_key");
        $activationFlag = $this->_coreHelper->getDefaultConfig($ext . "/license/activation_flag");
        $licensingMethod = $this->_coreHelper->getDefaultConfig($ext . "/license/get_online_license");
        $licenseCode = $this->_coreHelper->getDefaultConfigUncrypted($ext . "/license/activation_code");
        $domain = str_replace("{{unsecure_base_url}}", $this->_coreHelper->getDefaultConfig("web/unsecure/base_url"), $this->_coreHelper->getDefaultConfig("web/secure/base_url"));
        $registeredVersion = $this->_coreHelper->getDefaultConfig($ext . "/license/version");
        $currentVersion = $extension["version"];


        $wsParam = "&rv=" . $registeredVersion . "&cv=" . $currentVersion . "&namespace=" . $ext . "&activation_key=" . $activationKey . "&domain=" . $domain . "&magento=" . $this->_magentoVersion;
        $soapParams = [
            "method" => "get",
            "rv" => $registeredVersion,
            "cv" => $currentVersion,
            "namespace" => $ext,
            "activation_key" => $activationKey,
            "domain" => $domain,
            "magento" => $this->_magentoVersion,
            "licensemanager" => $this->_version
        ];

        // licence supprimée car mauvais ak ou ac
        if ($activationFlag == "1") {
            $this->addWarning($extension["label"], "flag", [$this->_urlBuilder->getUrl("adminhtml/system_config/edit/section/" . $ext . "/"), $extension["label"]]);
        } elseif ($registeredVersion != "" && $registeredVersion != $currentVersion && $licenseCode) { // Extension upgrade
            $this->_coreHelper->setDefaultConfig($ext . "/license/activation_code", "");
            $this->addWarning($extension["label"], "upgrade", [$registeredVersion, $currentVersion]);
            $this->_session->setData("update_" . $extension["value"], "true");
            $this->_refreshCache = true;
        } elseif (!$activationKey) { // no activation key not yet registered
            $this->_coreHelper->setDefaultConfig($ext . "/license/activation_code", "");
            $this->addWarning($extension["label"], "activation_key_warning", [$this->_urlBuilder->getUrl("adminhtml/system_config/edit/section/" . $ext . "/"), ($extension["label"])]);
            $this->_refreshCache = true;
        } elseif ($activationKey && (!$licenseCode || empty($licenseCode)) && !$licensingMethod) { // not yet activated --> manual activation
            $this->_coreHelper->setDefaultConfig($ext . "/license/activation_code", "");
            if ($this->_session->getData("update_" . $extension["value"]) != "true") {
                $this->addWarning($extension["label"], "license_code_warning", [$wsUrl . "method=post" . $wsParam]);
            } else {
                $this->addWarning($extension["label"], "license_code_updated_warning", [$wsUrl . "method=post" . $wsParam]);
            }
            $this->_refreshCache = true;
        } elseif ($activationKey && (!$licenseCode || empty($licenseCode)) && $licensingMethod) { // not yet activated --> automatic activation
            try {
                $options = ['location' => self::SOAP_URL,'uri' => self::SOAP_URI];
                if (!class_exists("\SoapClient")) {
                    throw new \Exception();
                }
                $api = new \SoapClient(null, $options);
                $ws = $api->checkActivation($soapParams);
                $wsResult = json_decode($ws);
                switch ($wsResult->status) {
                    case "success":
                        $this->addWarning($extension["label"], "ws_success", [$wsResult->message], true);
                        $this->_coreHelper->setDefaultConfig($ext . "/license/version", $wsResult->version);
                        $this->_coreHelper->setDefaultConfigCrypted($ext . "/license/activation_code", $wsResult->activation);
                        $this->_refreshCache = true;
                        break;
                    case "error":
                        $this->addWarning($extension["label"], "ws_failure", [$wsResult->message]);
                        $this->_coreHelper->setDefaultConfig($ext . "/license/activation_code", "");
                        $this->_refreshCache = true;
                        break;
                    default:
                        $this->addWarning($extension["label"], "ws_error", [$wsUrl . "method=post" . $wsParam]);
                        $this->_coreHelper->setDefaultConfig($ext . "/license/activation_code", "");
                        $this->_coreHelper->setDefaultConfig($ext . "/license/get_online_license", "0");
                        $this->_refreshCache = true;
                        break;
                }
            } catch (\Exception $e) {
                $this->addWarning($extension["label"], "ws_no_allowed", [$wsUrl . "method=post" . $wsParam]);
                $this->_coreHelper->setDefaultConfig($ext . "/license/activation_code", "");
                $this->_coreHelper->setDefaultConfig($ext . "/license/get_online_license", "0");
                $this->_refreshCache = true;
            }
        }
    }

    /**
     * @return string
     */
    public function getIdentity()
    {
        return md5($this->getText());
    }

    /**
     * @return int
     */
    public function getSeverity()
    {
        return self::SEVERITY_CRITICAL;
    }

    /**
     * @return string
     */
    public function getText()
    {
        $html = null;
        $count = count($this->_warnings);
        for ($i = 0; $i < $count; $i++) {
            $html.="<div style='padding-bottom:5px;" . (($i != 0) ? "margin-top:5px;" : "") . "" . (($i < $count - 1) ? "border-bottom:1px solid gray;" : "") . "'>" . $this->_warnings[$i] . "</div>";
        }

        return $html;
    }

    /**
     * @return boolean
     */
    public function isDisplayed()
    {
        return count($this->_warnings) > 0;
    }
}
