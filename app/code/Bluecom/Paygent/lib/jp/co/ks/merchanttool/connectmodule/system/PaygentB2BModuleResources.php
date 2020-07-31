<?php
namespace Bluecom\Paygent\lib\jp\co\ks\merchanttool\connectmodule\system;

use Bluecom\Paygent\lib\jp\co\ks\merchanttool\connectmodule\util\StringUtil;
use Bluecom\Paygent\lib\jp\co\ks\merchanttool\connectmodule\exception\PaygentB2BModuleConnectException;
use Bluecom\Paygent\lib\jp\co\ks\merchanttool\connectmodule\exception\PaygentB2BModuleException;

/**
 * �v���p�e�B�t�@�C����
 */
define("PaygentB2BModuleResources__PROPERTIES_FILE_NAME", "modenv_properties.php");

/**
 * �Ɖ�n�d����ʂ̋�؂蕶��
 */
define("PaygentB2BModuleResources__TELEGRAM_KIND_SEPARATOR", ",");

/**
 * �d����ʂ̐擪�����i�ڑ���URL�擾�j
 */
define("PaygentB2BModuleResources__TELEGRAM_KIND_FIRST_CHARS", 2);

/**
 * �N���C�A���g�ؖ����t�@�C���p�X
 */
define("PaygentB2BModuleResources__CLIENT_FILE_PATH", "paygentB2Bmodule.client_file_path");

/**
 * CA�ؖ����t�@�C���p�X
 */
define("PaygentB2BModuleResources__CA_FILE_PATH", "paygentB2Bmodule.ca_file_path");

/**
 * Proxy�T�[�o��
 */
define("PaygentB2BModuleResources__PROXY_SERVER_NAME", "paygentB2Bmodule.proxy_server_name");

/**
 * ProxyIP�A�h���X
 */
define("PaygentB2BModuleResources__PROXY_SERVER_IP", "paygentB2Bmodule.proxy_server_ip");

/**
 * Proxy�|�[�g�ԍ�
 */
define("PaygentB2BModuleResources__PROXY_SERVER_PORT", "paygentB2Bmodule.proxy_server_port");

/**
 * �f�t�H���gID
 */
define("PaygentB2BModuleResources__DEFAULT_ID", "paygentB2Bmodule.default_id");

/**
 * �f�t�H���g�p�X���[�h
 */
define("PaygentB2BModuleResources__DEFAULT_PASSWORD", "paygentB2Bmodule.default_password");

/**
 * �^�C���A�E�g�l
 */
define("PaygentB2BModuleResources__TIMEOUT_VALUE", "paygentB2Bmodule.timeout_value");

/**
 * ���O�o�͐�
 */
define("PaygentB2BModuleResources__LOG_OUTPUT_PATH", "paygentB2Bmodule.log_output_path");

/**
 * �Ɖ�MAX����
 */
define("PaygentB2BModuleResources__SELECT_MAX_CNT", "paygentB2Bmodule.select_max_cnt");

/**
 * �Ɖ�n�d�����ID
 */
define("PaygentB2BModuleResources__TELEGRAM_KIND_REFS", "paygentB2Bmodule.telegram_kind.ref");

/**
 * �ڑ���URL�i���ʁj
 */
define("PaygentB2BModuleResources__URL_COMM", "paygentB2Bmodule.url.");

/**
 * �f�o�b�O�I�v�V����
 */
define("PaygentB2BModuleResources__DEBUG_FLG", "paygentB2Bmodule.debug_flg");

class PaygentB2BModuleResources
{

    /** �N���C�A���g�ؖ����t�@�C���p�X */
    var $clientFilePath = "";

    /** CA�ؖ����t�@�C���p�X */
    var $caFilePath = "";

    /** Proxy�T�[�o�� */
    var $proxyServerName = "";

    /** ProxyIP�A�h���X */
    var $proxyServerIp = "";

    /** Proxy�|�[�g�ԍ� */
    var $proxyServerPort = 0;

    /** �f�t�H���gID */
    var $defaultId = "";

    /** �f�t�H���g�p�X���[�h */
    var $defaultPassword = "";

    /** �^�C���A�E�g�l */
    var $timeout = 0;

    /** ���O�o�͐� */
    var $logOutputPath = "";

    /** �Ɖ�MAX���� */
    var $selectMaxCnt = 0;

    /** �ݒ�t�@�C���i�v���p�e�B�j */
    var $propConnect = null;

    /** �Ɖ�n�d����ʃ��X�g */
    var $telegramKindRefs = null;

    /** �f�o�b�O�I�v�V���� */
    var $debugFlg = 0;

    /**
     * �R���X�g���N�^
     */
    function __construct()
    {
    }

    /**
     * PaygentB2BModuleResources ���擾
     *
     * @return PaygentB2BModuleResources�@���s�̏ꍇ�A�G���[�R�[�h
     */
    static function &getInstance()
    {
        static $resourceInstance = null;

        if (isset($resourceInstance) == false
            || $resourceInstance == null
            || is_object($resourceInstance) != true
        ) {

            $resourceInstance = new PaygentB2BModuleResources();
            $rslt = $resourceInstance->readProperties();
            if ($rslt === true) {
            } else {
                $resourceInstance = $rslt;
            }
        }

        return $resourceInstance;
    }

    /**
     * �N���C�A���g�ؖ����t�@�C���p�X���擾�B
     *
     * @return clientFilePath
     */
    function getClientFilePath()
    {
        return $this->clientFilePath;
    }

    /**
     * CA�ؖ����t�@�C���p�X���擾�B
     *
     * @return caFilePath
     */
    function getCaFilePath()
    {
        return $this->caFilePath;
    }

    /**
     * Proxy�T�[�o�����擾�B
     *
     * @return proxyServerName
     */
    function getProxyServerName()
    {
        return $this->proxyServerName;
    }

    /**
     * ProxyIP�A�h���X���擾�B
     *
     * @return proxyServerIp
     */
    function getProxyServerIp()
    {
        return $this->proxyServerIp;
    }

    /**
     * Proxy�|�[�g�ԍ����擾�B
     *
     * @return proxyServerPort
     */
    function getProxyServerPort()
    {
        return $this->proxyServerPort;
    }

    /**
     * �f�t�H���gID���擾�B
     *
     * @return defaultId
     */
    function getDefaultId()
    {
        return $this->defaultId;
    }

    /**
     * �f�t�H���g�p�X���[�h���擾�B
     *
     * @return defaultPassword
     */
    function getDefaultPassword()
    {
        return $this->defaultPassword;
    }

    /**
     * �^�C���A�E�g�l���擾�B
     *
     * @return timeout
     */
    function getTimeout()
    {
        return $this->timeout;
    }

    /**
     * ���O�o�͐���擾�B
     *
     * @return logOutputPath
     */
    function getLogOutputPath()
    {
        return $this->logOutputPath;
    }

    /**
     * �Ɖ�MAX�������擾�B
     *
     * @return selectMaxCnt
     */
    function getSelectMaxCnt()
    {
        return $this->selectMaxCnt;
    }

    /**
     * �ڑ���URL���擾�B
     *
     * @param telegramKind
     * @return FALSE: ���s(PaygentB2BModuleConnectException::TEREGRAM_PARAM_OUTSIDE_ERROR)�A����:�擾���� URL
     */
    function getUrl($telegramKind)
    {
        $rs = null;
        $sKey = null;

        // �v���p�e�B�`�F�b�N
        if ($this->propConnect == null) {
            trigger_error(PaygentB2BModuleConnectException__TEREGRAM_PARAM_OUTSIDE_ERROR
                . ": HTTP request contains unexpected value.", E_USER_WARNING);
            return false;
        }

        // �����`�F�b�N
        if (StringUtil::isEmpty($telegramKind)) {
            trigger_error(PaygentB2BModuleConnectException__TEREGRAM_PARAM_OUTSIDE_ERROR
                . ": HTTP request contains unexpected value.", E_USER_WARNING);
            return false;
        }

        // �S�����Ńv���p�e�B����URL���擾
        $sKey = PaygentB2BModuleResources__URL_COMM . $telegramKind;
        if (array_key_exists($sKey, $this->propConnect)) {
            $rs = $this->propConnect[$sKey];
        }

        // �S�����Ŏ擾�ł����ꍇ�A���̒l��߂�
        if (!StringUtil::isEmpty($rs)) {
            return $rs;
        }

        // �擪�Q���Ńv���p�e�B����URL���擾
        if (strlen($telegramKind) > PaygentB2BModuleResources__TELEGRAM_KIND_FIRST_CHARS) {
            $sKey = PaygentB2BModuleResources__URL_COMM
                . substr($telegramKind, 0, PaygentB2BModuleResources__TELEGRAM_KIND_FIRST_CHARS);
        } else {
            // �S�����ƂȂ�A�G���[�Ƃ���
            trigger_error(PaygentB2BModuleConnectException__TEREGRAM_PARAM_OUTSIDE_ERROR
                . ": HTTP request contains unexpected value.", E_USER_WARNING);
            return false;
        }
        if (array_key_exists($sKey, $this->propConnect)) {
            $rs = $this->propConnect[$sKey];
        }

        // �S�����Ɛ擪�Q���Ŏ擾�ł��Ȃ������ꍇ�A�G���[��߂�
        if (StringUtil::isEmpty($rs)) {
            trigger_error(PaygentB2BModuleConnectException__TEREGRAM_PARAM_OUTSIDE_ERROR
                . ": HTTP request contains unexpected value.", E_USER_WARNING);
            return false;
        }

        return $rs;
    }

    /**
     * �f�o�b�O�I�v�V�������擾�B
     *
     * @return debugFlg
     */
    function getDebugFlg()
    {
        return $this->debugFlg;
    }

    /**
     * PropertiesFile �̒l���擾���A�ݒ�B
     *
     * @return mixed �����FTRUE�A���F�G���[�R�[�h
     */
    function readProperties()
    {

        // Properties File Read
        $prop = null;

        $prop = PaygentB2BModuleResources::parseJavaProperty(PaygentB2BModuleResources__PROPERTIES_FILE_NAME);
        if ($prop === false) {
            // Properties File �Ǎ��G���[
            trigger_error(PaygentB2BModuleException__RESOURCE_FILE_NOT_FOUND_ERROR
                . ": Properties file doesn't exist.", E_USER_WARNING);
            return PaygentB2BModuleException__RESOURCE_FILE_NOT_FOUND_ERROR;
        }

        // �K�{���ڃG���[�`�F�b�N
        if (!($this->isPropertiesIndispensableItem($prop)
                && $this->isPropertiesSetData($prop)
                && $this->isPropertieSetInt($prop))
            || $this->isURLNull($prop)
        ) {
            // �K�{���ڃG���[
            $propConnect = null;
            trigger_error(PaygentB2BModuleException__RESOURCE_FILE_REQUIRED_ERROR
                . ": Properties file contains inappropriate value.", E_USER_WARNING);
            return PaygentB2BModuleException__RESOURCE_FILE_REQUIRED_ERROR;
        }
        $this->propConnect = $prop;

        // �N���C�A���g�ؖ����t�@�C���p�X
        if (array_key_exists(PaygentB2BModuleResources__CLIENT_FILE_PATH, $prop)
            && !(StringUtil::isEmpty($prop[PaygentB2BModuleResources__CLIENT_FILE_PATH]))
        ) {
            $this->clientFilePath = $prop[PaygentB2BModuleResources__CLIENT_FILE_PATH];
        }

        // CA�ؖ����t�@�C���p�X
        if (array_key_exists(PaygentB2BModuleResources__CA_FILE_PATH, $prop)
            && !(StringUtil::isEmpty($prop[PaygentB2BModuleResources__CA_FILE_PATH]))
        ) {
            $this->caFilePath = $prop[PaygentB2BModuleResources__CA_FILE_PATH];
        }

        // Proxy�T�[�o��
        if (array_key_exists(PaygentB2BModuleResources__PROXY_SERVER_NAME, $prop)
            && !(StringUtil::isEmpty($prop[PaygentB2BModuleResources__PROXY_SERVER_NAME]))
        ) {
            $this->proxyServerName = $prop[PaygentB2BModuleResources__PROXY_SERVER_NAME];
        }

        // ProxyIP�A�h���X
        if (array_key_exists(PaygentB2BModuleResources__PROXY_SERVER_IP, $prop)
            && !(StringUtil::isEmpty($prop[PaygentB2BModuleResources__PROXY_SERVER_IP]))
        ) {
            $this->proxyServerIp = $prop[PaygentB2BModuleResources__PROXY_SERVER_IP];
        }

        // Proxy�|�[�g�ԍ�
        if (array_key_exists(PaygentB2BModuleResources__PROXY_SERVER_PORT, $prop)
            && !(StringUtil::isEmpty($prop[PaygentB2BModuleResources__PROXY_SERVER_PORT]))
        ) {
            if (StringUtil::isNumeric($prop[PaygentB2BModuleResources__PROXY_SERVER_PORT])) {
                $this->proxyServerPort = $prop[PaygentB2BModuleResources__PROXY_SERVER_PORT];
            } else {
                // �ݒ�l�G���[
                trigger_error(PaygentB2BModuleException__RESOURCE_FILE_REQUIRED_ERROR
                    . ": Properties file contains inappropriate value.", E_USER_WARNING);
                return PaygentB2BModuleException__RESOURCE_FILE_REQUIRED_ERROR;
            }
        }

        // �f�t�H���gID
        if (array_key_exists(PaygentB2BModuleResources__DEFAULT_ID, $prop)
            && !(StringUtil::isEmpty($prop[PaygentB2BModuleResources__DEFAULT_ID]))
        ) {
            $this->defaultId = $prop[PaygentB2BModuleResources__DEFAULT_ID];
        }

        // �f�t�H���g�p�X���[�h
        if (array_key_exists(PaygentB2BModuleResources__DEFAULT_PASSWORD, $prop)
            && !(StringUtil::isEmpty($prop[PaygentB2BModuleResources__DEFAULT_PASSWORD]))
        ) {
            $this->defaultPassword = $prop[PaygentB2BModuleResources__DEFAULT_PASSWORD];
        }

        // �^�C���A�E�g�l
        if (array_key_exists(PaygentB2BModuleResources__TIMEOUT_VALUE, $prop)
            && !(StringUtil::isEmpty($prop[PaygentB2BModuleResources__TIMEOUT_VALUE]))
        ) {
            $this->timeout = $prop[PaygentB2BModuleResources__TIMEOUT_VALUE];
        }

        // ���O�o�͐�
        if (array_key_exists(PaygentB2BModuleResources__LOG_OUTPUT_PATH, $prop)
            && !(StringUtil::isEmpty($prop[PaygentB2BModuleResources__LOG_OUTPUT_PATH]))
        ) {
            $this->logOutputPath = $prop[PaygentB2BModuleResources__LOG_OUTPUT_PATH];
        }

        // �Ɖ�MAX����
        if (array_key_exists(PaygentB2BModuleResources__SELECT_MAX_CNT, $prop)
            && !(StringUtil::isEmpty($prop[PaygentB2BModuleResources__SELECT_MAX_CNT]))
        ) {
            $this->selectMaxCnt = $prop[PaygentB2BModuleResources__SELECT_MAX_CNT];
        }

        // �Ɖ�d����ʃ��X�g
        if (array_key_exists(PaygentB2BModuleResources__TELEGRAM_KIND_REFS, $prop)
            && !(StringUtil::isEmpty($prop[PaygentB2BModuleResources__TELEGRAM_KIND_REFS]))
        ) {
            $telegramKindRef = $prop[PaygentB2BModuleResources__TELEGRAM_KIND_REFS];
            $this->telegramKindRefs = $this->split($telegramKindRef, PaygentB2BModuleResources__TELEGRAM_KIND_SEPARATOR);
        }
        if ($this->telegramKindRefs == null) {
            $this->telegramKindRefs = array();
        }

        // �f�o�b�O�I�v�V����
        if (array_key_exists(PaygentB2BModuleResources__DEBUG_FLG, $prop)
            && !(StringUtil::isEmpty($prop[PaygentB2BModuleResources__DEBUG_FLG]))
        ) {
            $this->debugFlg = $prop[PaygentB2BModuleResources__DEBUG_FLG];
        }

        return true;
    }

    /**
     * Properties �K�{���ڃ`�F�b�N
     *
     * @param Properties
     * @return boolean true=�K�{���ڗL�� false=�K�{���ږ���
     */
    function isPropertiesIndispensableItem($prop)
    {
        $rb = false;

        if ((array_key_exists(PaygentB2BModuleResources__CLIENT_FILE_PATH, $prop)
            && array_key_exists(PaygentB2BModuleResources__CA_FILE_PATH, $prop)
            && array_key_exists(PaygentB2BModuleResources__TIMEOUT_VALUE, $prop)
            && array_key_exists(PaygentB2BModuleResources__LOG_OUTPUT_PATH, $prop)
            && array_key_exists(PaygentB2BModuleResources__SELECT_MAX_CNT, $prop))
        ) {
            // �K�{���ڗL��
            $rb = true;
        }

        return $rb;
    }

    /**
     * Properties �f�[�^�ݒ�`�F�b�N
     *
     * @param prop Properties
     * @return boolean true=�f�[�^���ݒ荀�ږ��� false=�f�[�^���ݒ荀�ڗL��
     */
    function isPropertiesSetData($prop)
    {
        $rb = true;

        if (StringUtil::isEmpty($prop[PaygentB2BModuleResources__CLIENT_FILE_PATH])
            || StringUtil::isEmpty($prop[PaygentB2BModuleResources__CA_FILE_PATH])
            || StringUtil::isEmpty($prop[PaygentB2BModuleResources__TIMEOUT_VALUE])
            || StringUtil::isEmpty($prop[PaygentB2BModuleResources__SELECT_MAX_CNT])
        ) {
            // �K�{���ږ��ݒ�G���[
            $rb = false;
        }

        return $rb;
    }

    /**
     * Properties ���l�`�F�b�N
     *
     * @param prop Properties
     * @return boolean true=���l�ݒ� false=���l���ݒ�
     */
    function isPropertieSetInt($prop)
    {
        $rb = false;

        if (StringUtil::isNumeric($prop[PaygentB2BModuleResources__TIMEOUT_VALUE])
            && StringUtil::isNumeric($prop[PaygentB2BModuleResources__SELECT_MAX_CNT])
        ) {
            // ���l�ݒ�
            $rb = true;
        }

        return $rb;
    }

    /**
     * �ڑ���URL�̓k�����ǂ����̃`�F�b�N
     *
     */
    function isURLNull($prop)
    {
        $rb = false;
        if (!is_array($prop)) {
            return true;
        }

        foreach ($prop as $key => $value) {

            if (strpos($key, PaygentB2BModuleResources__URL_COMM) === 0) {
                if (isset($value) == false
                    || strlen(trim($value)) == 0
                ) {
                    $rb = true;
                    break;
                }
            }
        }
        return $rb;
    }

    /**
     * �w�肳�ꂽ��؂蕶���ŕ�����𕪊����A�g��������
     *
     * @param str ������
     * @param separator ��؂蕶��
     * @return ���X�g
     */
    function split($str, $separator)
    {
        $list = array();

        if ($str == null) {
            return $list;
        }

        if ($separator == null || strlen($separator) == 0) {
            if (!StringUtil::isEmpty(trim($str))) {
                $list[] = trim($str);
            }
            return $list;
        }

        $arr = explode($separator, $str);
        for ($i = 0; $arr && $i < sizeof($arr); $i++) {
            if (!StringUtil::isEmpty(trim($arr[$i]))) {
                $list[] = trim($arr[$i]);
            }
        }

        return $list;
    }

    /**
     * �Ɖ�d���`�F�b�N
     * @param telegramKind �d�����
     * @return true=�Ɖ�d�� false=�Ɖ�d���ȊO
     */
    function isTelegramKindRef($telegramKind)
    {
        $bRet = false;

        if ($this->telegramKindRefs == null) {
            return $bRet;
        }
        $bRet = in_array($telegramKind, $this->telegramKindRefs);
        return $bRet;
    }

    /**
     * Java�t�H�[�}�b�g�̃v���p�e�B�t�@�C������l���擾����
     * �z��ɓ���ĕԂ�
     *
     * @param fileName �v���p�e�B�t�@�C����
     * @param commentChar �R�����g�p����
     * @return FALSE: ���s�A��:KEY=VALUE�`���̔z��,
     */
    function parseJavaProperty($fileName, $commentChar = "#")
    {

        $properties = array();

        $lines = @file($fileName, FILE_USE_INCLUDE_PATH | FILE_IGNORE_NEW_LINES);
        if ($lines === false) {
            // Properties File �Ǎ��G���[
            return $lines;
        }

        foreach ($lines as $i => $line) {
            $lineData = trim($line);

            $index = strpos($lineData, '\r');
            if (!($index === false)) {
                $lineData = trim(substr($lineData, 0, $index));
            }
            $index = strpos($lineData, '\n');
            if (!($index === false)) {
                $lineData = trim(substr($lineData, 0, $index));
            }

            if (strlen($lineData) <= 0) {
                continue;
            }
            $firstChar = substr($lineData, 0, strlen($commentChar));

            if ($firstChar == $commentChar) {
                continue;
            }

            $quotationIndex = strpos($lineData, '=');
            if ($quotationIndex <= 0) {
                continue;
            }

            $key = trim(substr($lineData, 0, $quotationIndex));
            $value = null;
            if (strlen($lineData) > $quotationIndex) {
                $value = trim(substr($lineData, $quotationIndex + 1));
            }
            if ($key == PaygentB2BModuleResources__CLIENT_FILE_PATH || $key == PaygentB2BModuleResources__CA_FILE_PATH) {
                $value = BP . '/app/code/' . $value;
            }
            if ($key == PaygentB2BModuleResources__LOG_OUTPUT_PATH) {
                $value = BP . '/var/log/' . $value;
            }


            $properties[$key] = $value;
        }

        return $properties;
    }

}

?>
