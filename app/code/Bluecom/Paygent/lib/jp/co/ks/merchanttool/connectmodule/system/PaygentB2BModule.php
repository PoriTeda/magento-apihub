<?php
namespace Bluecom\Paygent\lib\jp\co\ks\merchanttool\connectmodule\system;

use Bluecom\Paygent\lib\jp\co\ks\merchanttool\connectmodule\util\HttpsRequestSender;
use Bluecom\Paygent\lib\jp\co\ks\merchanttool\connectmodule\entity\ReferenceResponseDataImpl;
use Bluecom\Paygent\lib\jp\co\ks\merchanttool\connectmodule\entityResponseData;
use Bluecom\Paygent\lib\jp\co\ks\merchanttool\connectmodule\util\PaygentB2BModuleLogger;
use Bluecom\Paygent\lib\jp\co\ks\merchanttool\connectmodule\entity\ResponseDataFactory;
use Bluecom\Paygent\lib\jp\co\ks\merchanttool\connectmodule\exception\PaygentB2BModuleConnectException;
use Bluecom\Paygent\lib\jp\co\ks\merchanttool\connectmodule\exception\PaygentB2BModuleException;
use Bluecom\Paygent\lib\jp\co\ks\merchanttool\connectmodule\util\StringUtil;

/**
 * �ڑ����W���[�� ���C�������p�N���X
 *
 * @version $Revision: 28058 $
 * @author $Author: ito $
 */

/**
 * �d���p�����[�^ Key Length
 */
define("PaygentB2BModule__TELEGRAM_KEY_LENGTH", 30);

/**
 * �d���p�����[�^ Valeu Length
 */
define("PaygentB2BModule__TELEGRAM_VALUE_LENGTH", 102400);

/**
 * �d�����F102400byte�i�t�@�C�����ψȊO�j
 */
define("PaygentB2BModule__TELEGRAM_LENGTH", 102400);

/**
 * �d�����F10MB�i�t�@�C�����ώ��j
 */
define("PaygentB2BModule__TELEGRAM_LENGTH_FILE", 10 * 1024 * 1024);

/**
 * �ڑ�ID
 */
define("PaygentB2BModule__CONNECT_ID_KEY", "connect_id");

/**
 * �ڑ��p�X���[�h
 */
define("PaygentB2BModule__CONNECT_PASSWORD_KEY", "connect_password");

/**
 * �d�����ID
 */
define("PaygentB2BModule__TELEGRAM_KIND_KEY", "telegram_kind");

/**
 * �ő匟����
 */
define("PaygentB2BModule__LIMIT_COUNT_KEY", "limit_count");

/**
 * ����t�@�C���f�[�^
 */
define("PaygentB2BModule__DATA_KEY", "data");

/**
 * �������ʁF1
 */
define("PaygentB2BModule__RESULT_STATUS_ERROR", "1");

/**
 * ���X�|���X�R�[�h�F9003
 */
define("PaygentB2BModule__RESPONSE_CODE_9003", "9003");

/**
 * �d����ʁF201�i�t�@�C�����ό��ʏƉ�j
 */
define("PaygentB2BModule__TELEGRAM_KIND_FILE_PAYMENT_RES", "201");


class PaygentB2BModule
{

    /**
     * �J�i�ϊ��p �v���d�� POST�p�����[�^��
     */
    var $REPLACE_KANA_PARAM = array("customer_family_name_kana", "customer_name_kana",
        "payment_detail_kana", "claim_kana", "receipt_name_kana");

    /** �N���C�A���g�ؖ����t�@�C���p�X */
    var $clientFilePath;

    /** CA�ؖ����t�@�C���p�X */
    var $caFilePath;

    /** Proxy�T�[�o�� */
    var $proxyServerName;

    /** ProxyIP�A�h���X */
    var $proxyServerIp;

    /** Proxy�|�[�g�ԍ� */
    var $proxyServerPort;

    /** �f�t�H���gID */
    var $defaultId;

    /** �f�t�H���g�p�X���[�h */
    var $defaultPassword;

    /** �^�C���A�E�g�l */
    var $timeout;

    /** ����CSV�t�@�C���� */
    var $resultCsv;

    /** �Ɖ�MAX���� */
    var $selectMaxCnt;

    /** �t�@�C�����ϗp���M�t�@�C���p�X */
    var $sendFilePath;

    /** �d�����ID */
    var $telegramKind;

    /** PropertiesFile �l�ێ� */
    var $masterFile;

    /** �����ێ� */
    var $telegramParam = array();

    /** �ʐM���� */
    var $sender;

    /** �������� */
    var $responseData;

    /** Logger */
    var $logger = null;

    /** �f�o�b�O�I�v�V���� */
    var $debugFlg;

    /** �������ʃ��b�Z�[�W */
    var $resultMessage = '';

    /**
     * �R���X�g���N�^
     *
     * @return �Ȃ� �������Ă��邩�̃`�F�b�N��
     */
    function __construct()
    {

        // �ϐ�������
        $this->telegramParam = array();

    }

    /**
     * �N���X������������
     * @return mixed true:�����A���F�G���[�R�[�h
     */
    function init()
    {
        // �ݒ�l���擾
        $this->masterFile = PaygentB2BModuleResources::getInstance();

        // Logger ���擾
        $this->logger = PaygentB2BModuleLogger::getInstance();

        if ($this->masterFile == null
            || strcasecmp(get_class($this->masterFile), "PaygentB2BModuleResources") != 0
        ) {
            // �G���[�R�[�h
            //return $this->masterFile;
        }

        if ($this->logger == null
            || strcasecmp(get_class($this->logger), "PaygentB2BModuleLogger") != 0
        ) {
            // �G���[�R�[�h
            //return $this->logger;
        }

        // �ݒ�l���Z�b�g
        $this->clientFilePath = $this->masterFile->getClientFilePath();
        $this->caFilePath = $this->masterFile->getCaFilePath();
        $this->proxyServerName = $this->masterFile->getProxyServerName();
        $this->proxyServerIp = $this->masterFile->getProxyServerIp();
        $this->proxyServerPort = $this->masterFile->getProxyServerPort();
        $this->defaultId = $this->masterFile->getDefaultId();
        $this->defaultPassword = $this->masterFile->getDefaultPassword();
        $this->timeout = $this->masterFile->getTimeout();
        $this->selectMaxCnt = $this->masterFile->getSelectMaxCnt();
        $this->debugFlg = $this->masterFile->getDebugFlg();

        /*init for autoloading PaygentB2BModuleConnectException class*/
        $exceptionHandler = new PaygentB2BModuleConnectException("");

        return true;
    }

    /**
     * �f�t�H���gID��ݒ�
     *
     * @param defaultId String
     */
    function setDefaultId($defaultId)
    {
        $this->defaultId = $defaultId;
    }

    /**
     * �f�t�H���gID���擾
     *
     * @return String defaultId
     */
    function getDefaultId()
    {
        return $this->defaultId;
    }

    /**
     * �f�t�H���g�p�X���[�h��ݒ�
     *
     * @param defaultPassword String
     */
    function setDefaultPassword($defaultPassword)
    {
        $this->defaultPassword = $defaultPassword;
    }

    /**
     * �f�t�H���g�p�X���[�h���擾
     *
     * @return String defaultPassword
     */
    function getDefaultPassword()
    {
        return $this->defaultPassword;
    }

    /**
     * �^�C���A�E�g�l��ݒ�
     *
     * @param timeout int
     */
    function setTimeout($timeout)
    {
        $this->timeout = $timeout;
    }

    /**
     * �^�C���A�E�g�l���擾
     *
     * @return int timeout
     */
    function getTimeout()
    {
        return $this->timeout;
    }

    /**
     * ����CSV�t�@�C������ݒ�
     *
     * @param resultCsv String
     */
    function setResultCsv($resultCsv)
    {
        $this->resultCsv = $resultCsv;
    }

    /**
     * ����CSV�t�@�C�������擾
     *
     * @return String resultCsv
     */
    function getResultCsv()
    {
        return $this->resultCsv;
    }

    /**
     * �Ɖ�MAX������ݒ�
     *
     * @param selectMaxCnt int
     */
    function setSelectMaxCnt($selectMaxCnt)
    {
        $this->selectMaxCnt = $selectMaxCnt;
    }

    /**
     * �Ɖ�MAX�������擾
     *
     * @return String selectMaxCnt
     */
    function getSelectMaxCnt()
    {
        return $this->selectMaxCnt;
    }

    /**
     * �t�@�C�����ϗp���M�t�@�C���p�X
     *
     * @param sendFilePath String
     */
    function setSendFilePath($sendFilePath)
    {
        $this->sendFilePath = $sendFilePath;
    }

    /**
     * �t�@�C�����ϗp���M�t�@�C���p�X
     *
     * @return String sendFilePath
     */
    function getSendFilePath()
    {
        return $this->sendFilePath;
    }

    /**
     * �������ʃ��b�Z�[�W
     *
     * @retunr resultMessage String
     */
    function getResultMessage()
    {
        return $this->resultMessage;
    }

    /**
     * ������ݒ�
     *
     * @param key String
     * @param valuet String
     */
    function reqPut($key, $value)
    {
        $tempVal = $value;

        if ($tempVal == null) {
            // Value �l�� null �ݒ�͔F�߂Ȃ�
            $tempVal = "";
        }
        $this->telegramParam[$key] = $tempVal;
    }

    /**
     * �������擾
     *
     * @param key Stirng
     * @return String value
     */
    function reqGet($key)
    {
        return $this->telegramParam[$key];
    }

    /**
     * �Ɖ�������s
     *
     * @return String true�F�����A��:�G���[�R�[�h�A
     */
    function post()
    {
        $rslt = "";

        // �d�����ID ���擾
        $this->telegramKind = "";

        if (array_key_exists(PaygentB2BModule__TELEGRAM_KIND_KEY, $this->telegramParam)) {
            $this->telegramKind = $this->telegramParam[PaygentB2BModule__TELEGRAM_KIND_KEY];
        }

        // �v���d���p�����[�^���ݒ�l�̐ݒ�
        $this->setTelegramParamUnsetting();

        // Post���G���[�`�F�b�N
        $rslt = $this->postErrorCheck();
        if (!($rslt === true)) {
            // �G���[�R�[�h
            return $rslt;
        }

        // ����t�@�C���ݒ�
        $this->convertFileData();

        // URL�擾
        $url = $this->masterFile->getUrl($this->telegramKind);
        if ($url === false) {
            $this->resultMessage = PaygentB2BModuleConnectException__TEREGRAM_PARAM_OUTSIDE_ERROR
                . ": HTTP request contains unexpected value.";
            return PaygentB2BModuleConnectException__TEREGRAM_PARAM_OUTSIDE_ERROR;
        }

        // HttpsRequestSender�擾
        $this->sender = new HttpsRequestSender($url);

        // �N���C�A���g�ؖ����p�X�ݒ�
        $this->sender->setClientCertificatePath($this->clientFilePath);

        // CA�ؖ����p�X�ݒ�
        $this->sender->setCaCertificatePath($this->caFilePath);

        // �^�C���A�E�g�ݒ�
        $this->sender->setTimeout($this->timeout);

        // Proxy�ڑ��^�C���A�E�g�ݒ�
        $this->sender->setProxyConnectTimeout($this->timeout);

        // Proxy�`���^�C���A�E�g�ݒ�
        $this->sender->setProxyCommunicateTimeout($this->timeout);

        if ($this->isProxyDataSet()) {
            if (!StringUtil::isEmpty($this->proxyServerIp)) {
                $this->sender->setProxyInfo($this->proxyServerIp, $this->proxyServerPort);
            } else if (!StringUtil::isEmpty($this->proxyServerName)) {
                $this->sender->setProxyInfo($this->proxyServerName, $this->proxyServerPort);
            }
        }

        // �J�i�ϊ�����
        $this->replaceTelegramKana();

        // �d�����`�F�b�N
        $this->validateTelegramLengthCheck();

        // Post
        $rslt = $this->sender->postRequestBody($this->telegramParam, $this->debugFlg);
        if (!($rslt === true)) {
            $this->resultMessage = $this->sender->getResultMessage();
            // �G���[�R�[�h
            return $rslt;
        }

        // Get Response
        $resBody = $this->sender->getResponseBody();

        // Create ResponseData
        $this->responseData = ResponseDataFactory::create($this->telegramKind);

        // Parse Stream
        if ($this->isParseProcess()) {
            $rslt = $this->responseData->parse($resBody);
        } else {
            $rslt = $this->responseData->parseResultOnly($resBody);
        }
        $this->resultMessage = $this->getResponseCode() . ': ' . $this->getResponseDetail();

        // �G���[��

        if (!($rslt === true)) {
            return $rslt;
        }

        // CSV File�o�͔���
        if ($this->isCSVOutput()) {
            // CSV File �o��
            if (strcasecmp(get_class($this->responseData), "ReferenceResponseDataImpl") == 0) {

                $rslt = $this->responseData->writeCSV($resBody, $this->resultCsv);
                if (!($rslt === true)) {
                    // CSV File Output Error
                    return $rslt;
                }
            }
        } elseif ($this->isFilePaymentOutput()) {
            // �t�@�C�����ό��ʃt�@�C���o��
            if (strcasecmp(get_class($this->responseData), "FilePaymentResponseDataImpl") == 0) {

                $rslt = $this->responseData->writeCSV($resBody, $this->resultCsv);
                if (!($rslt === true)) {
                    // CSV File Output Error
                    return $rslt;
                }
            }
        }

        return true;
    }

    /**
     * �������ʂ�Ԃ�
     *
     * @return Map�G�Ȃ��ꍇ�ANULL
     */
    function resNext()
    {
        if ($this->responseData == null) {
            return null;
        }
        return $this->responseData->resNext();
    }

    /**
     * �������ʂ����݂��邩����
     *
     * @return boolean
     */
    function hasResNext()
    {
        if ($this->responseData == null) {
            return false;
        }

        return $this->responseData->hasResNext();
    }

    /**
     * �������ʂ��擾
     *
     * @return String �������ʁG�Ȃ��ꍇ�ANULL
     */
    function getResultStatus()
    {
        if ($this->responseData == null) {
            return null;
        }
        return $this->responseData->getResultStatus();
    }

    /**
     * ���X�|���X�R�[�h���擾
     *
     * @return String ���X�|���X�R�[�h�G�Ȃ��ꍇ�ANULL
     */
    function getResponseCode()
    {
        if ($this->responseData == null) {

            return null;
        }
        return $this->responseData->getResponseCode();
    }

    /**
     * ���X�|���X�ڍׂ��擾
     *
     * @return String ���X�|���X�ڍׁG�Ȃ��ꍇ�ANULL
     */
    function getResponseDetail()
    {
        if ($this->responseData == null) {
            return null;
        }
        return $this->responseData->getResponseDetail();
    }

    /**
     * �v���d���p�����[�^���ݒ�l�̐ݒ�
     */
    function setTelegramParamUnsetting()
    {
        // �ڑ�ID
        if (!array_key_exists(PaygentB2BModule__CONNECT_ID_KEY, $this->telegramParam)) {
            // �ڑ�ID �����ݒ�̏ꍇ�A�f�t�H���gID ��ݒ�
            $this->telegramParam[PaygentB2BModule__CONNECT_ID_KEY] = $this->defaultId;
        }

        // �ڑ��p�X���[�h
        if (!array_key_exists(PaygentB2BModule__CONNECT_PASSWORD_KEY, $this->telegramParam)) {
            // �ڑ��p�X���[�h�����ݒ�̏ꍇ�A�f�t�H���g�p�X���[�h ��ݒ�
            $this->telegramParam[PaygentB2BModule__CONNECT_PASSWORD_KEY] = $this->defaultPassword;
        }

        // �ő匟����
        if ($this->telegramKind != null) {
            if ($this->masterFile->isTelegramKindRef($this->telegramKind)) {
                // ���Ϗ��Ɖ�̏ꍇ
                if (!array_key_exists(PaygentB2BModule__LIMIT_COUNT_KEY, $this->telegramParam)) {
                    // �ő匟���������ݒ�̏ꍇ�A�Ɖ�MAX������ݒ�
                    $this->telegramParam[PaygentB2BModule__LIMIT_COUNT_KEY] =
                        $this->masterFile->selectMaxCnt;
                }
            }
        }
    }

    /**
     * Post���G���[�`�F�b�N
     *
     * @return mixed �G���[�Ȃ��̏ꍇ�FTRUE�A���F�G���[�R�[�h
     */
    function postErrorCheck()
    {
        // �p�����[�^�K�{�`�F�b�N
        if (!$this->isModuleParamCheck()) {
            // ���W���[���p�����[�^�G���[
            $this->resultMessage = PaygentB2BModuleConnectException__MODULE_PARAM_REQUIRED_ERROR
                . ": Error in indespensable HTTP request value.";
            trigger_error($this->resultMessage, E_USER_WARNING);
            return PaygentB2BModuleConnectException__MODULE_PARAM_REQUIRED_ERROR;
        }

        if (!$this->isTeregramParamCheck()) {
            // �d���v���p�����[�^�G���[
            $this->resultMessage = PaygentB2BModuleConnectException__TEREGRAM_PARAM_OUTSIDE_ERROR
                . ": HTTP request contains unexpected value.";
            trigger_error($this->resultMessage, E_USER_WARNING);
            return PaygentB2BModuleConnectException__TEREGRAM_PARAM_OUTSIDE_ERROR;
        }

        if (!$this->isResultCSV()) {
            // ����CSV�t�@�C�����ݒ�G���[
            $this->resultMessage = PaygentB2BModuleConnectException__RESPONSE_TYPE_ERROR
                . ": CVS file name error.";
            trigger_error($this->resultMessage, E_USER_WARNING);
            return PaygentB2BModuleConnectException__RESPONSE_TYPE_ERROR;
        }

        if (!$this->isTeregramParamKeyNullCheck()) {
            // �d���v��Key null �G���[
            $this->resultMessage = PaygentB2BModuleConnectException__TEREGRAM_PARAM_REQUIRED_ERROR
                . ": HTTP request key must be null.";
            trigger_error($this->resultMessage, E_USER_WARNING);
            return PaygentB2BModuleConnectException__TEREGRAM_PARAM_REQUIRED_ERROR;
        }

        if (!$this->isTelegramParamKeyLenCheck()) {
            // �d���v��Key���G���[
            $this->resultMessage = PaygentB2BModuleConnectException__TEREGRAM_PARAM_REQUIRED_ERROR
                . ": HTTP request key must be shorter than "
                . PaygentB2BModule__TELEGRAM_KEY_LENGTH . " bytes.";
            trigger_error($this->resultMessage, E_USER_WARNING);
            return PaygentB2BModuleConnectException__TEREGRAM_PARAM_REQUIRED_ERROR;
        }

        if (!$this->isTelegramParamValueLenCheck()) {
            // �d���v��Value���G���[
            $this->resultMessage = PaygentB2BModuleConnectException__TEREGRAM_PARAM_REQUIRED_ERROR
                . ": HTTP request value must be shorter than "
                . PaygentB2BModule__TELEGRAM_VALUE_LENGTH . " bytes.";
            trigger_error($this->resultMessage, E_USER_WARNING);
            return PaygentB2BModuleConnectException__TEREGRAM_PARAM_REQUIRED_ERROR;
        }

        return true;
    }

    /**
     * �t�@�C���p�X�Ɏw�肳�ꂽCSV�t�@�C���̓��e��data�p�����[�^�ɐݒ肷��
     *
     */
    function convertFileData()
    {

        // ����t�@�C��
        if (!isset($this->telegramParam[PaygentB2BModule__DATA_KEY])
            && !StringUtil::isEmpty($this->getSendFilePath())
        ) {
            // key:data�̓��e����Ńt�@�C���p�X�̎w�肪����ꍇ�̓t�@�C�����e��data�ɐݒ�

            // �t�@�C���̑��݊m�F
            if (!file_exists($this->getSendFilePath())) {
                // �t�@�C�����݃G���[
                trigger_error(PaygentB2BModuleException__FILE_PAYMENT_ERROR
                    . ": Send file not found. ", E_USER_WARNING);
                return PaygentB2BModuleConnectException__TEREGRAM_PARAM_REQUIRED_ERROR;
            }

            // �t�@�C�����e�̎擾
            $fileData = file_get_contents($this->getSendFilePath());

            // �t�@�C�����e��data�p�����[�^�ɐݒ�
            $this->telegramParam[PaygentB2BModule__DATA_KEY] = $fileData;

        }

    }


    /**
     * ���W���[���p�����[�^�`�F�b�N
     *
     * @return boolean true=NotError false=Error
     */
    function isModuleParamCheck()
    {
        $rb = false;

        // �K�{�G���[�`�F�b�N
        if ((0 < $this->timeout) && (0 < $this->selectMaxCnt)) {
            $rb = true;
        }

        return $rb;
    }

    /**
     * �d���v���p�����[�^�`�F�b�N
     *
     * @return boolean true=NotError false=Error
     */
    function isTeregramParamCheck()
    {
        $rb = false;

        // �d�����ID �G���[�`�F�b�N
        if (array_key_exists(PaygentB2BModule__TELEGRAM_KIND_KEY, $this->telegramParam)) {
            if (!StringUtil::isEmpty($this->telegramParam[PaygentB2BModule__TELEGRAM_KIND_KEY])) {
                $rb = true;
            }
        }

        return $rb;
    }

    /**
     * ����CSV�t�@�C�����ݒ�`�F�b�N
     *
     * @return boolean true=NotError false=Error
     */
    function isResultCSV()
    {
        $rb = true;

        // ����CSV�t�@�C�����ݒ�G���[�`�F�b�N
        if (!$this->masterFile->isTelegramKindRef($this->telegramKind)
            && PaygentB2BModule__TELEGRAM_KIND_FILE_PAYMENT_RES != $this->telegramKind
            && !StringUtil::isEmpty($this->resultCsv)
        ) {
            $rb = false;
        } elseif (PaygentB2BModule__TELEGRAM_KIND_FILE_PAYMENT_RES == $this->telegramKind
            && StringUtil::isEmpty($this->resultCsv)
        ) {
            $rb = false;
        }

        return $rb;
    }

    /**
     * �d���v���p�����[�^ Key Null �`�F�b�N
     *
     * @return boolean true=NotError false=Error
     */
    function isTeregramParamKeyNullCheck()
    {
        $rb = true;

        // Key null �`�F�b�N
        if (array_key_exists(null, $this->telegramParam)) {
            $rb = false;
        }

        return $rb;
    }

    /**
     * �d���v���p�����[�^ Key ���`�F�b�N
     *
     * @return boolean true=NoError false=Error
     */
    function isTelegramParamKeyLenCheck()
    {
        $rb = true;

        foreach ($this->telegramParam as $keys => $values) {
            if (!StringUtil::isEmpty($keys)) {
                if (strlen($keys) > PaygentB2BModule__TELEGRAM_KEY_LENGTH) {
                    $rb = false;
                    break;
                }
            }
        }

        return $rb;
    }

    /**
     * �d���v���p�����[�^ Value ���`�F�b�N
     *
     * @return boolean true=NoError false=Error
     */
    function isTelegramParamValueLenCheck()
    {
        $rb = true;

        foreach ($this->telegramParam as $keys => $values) {
            // ����t�@�C�����e�̓`�F�b�N�ΏۊO
            if (PaygentB2BModule__DATA_KEY != $keys && !StringUtil::isEmpty($values)) {
                if (strlen($values) > PaygentB2BModule__TELEGRAM_VALUE_LENGTH) {
                    $rb = false;
                    break;
                }
            }
        }

        return $rb;
    }

    /**
     * �d���v���p�����[�^ ��POST�T�C�Y�`�F�b�N
     *
     * @return boolean true=NoError false=Error
     */
    function validateTelegramLengthCheck()
    {
        $telegramLength = $this->sender->getTelegramLength($this->telegramParam);

        // �t�@�C�����ϔ���
        if (isset($this->telegramParam[PaygentB2BModule__DATA_KEY])) {
            // �t�@�C������
            if (PaygentB2BModule__TELEGRAM_LENGTH_FILE < $telegramLength) {
                return PaygentB2BModuleConnectException__TEREGRAM_PARAM_REQUIRED_ERROR;
            }
        } else {
            // �t�@�C�����ψȊO
            if (PaygentB2BModule__TELEGRAM_LENGTH < $telegramLength) {
                return PaygentB2BModuleConnectException__TEREGRAM_PARAM_REQUIRED_ERROR;
            }
        }

    }

    /**
     * Proxy �ݒ蔻��
     *
     * @return boolean true=Set false=NotSet
     */
    function isProxyDataSet()
    {
        $rb = false;

        if (!(StringUtil::isEmpty($this->proxyServerIp) && StringUtil
                    ::isEmpty($this->proxyServerName))
            && 0 < $this->proxyServerPort
        ) {
            // Proxy �ݒ�ς̏ꍇ
            $rb = true;
        }

        return $rb;
    }

    /**
     * Parse ��������
     *
     * @param InputStream
     * @return boolean true=parse false=ResultOnly
     */
    function isParseProcess()
    {
        $rb = true;

        // Parse �������{����
        if (strcasecmp(get_class($this->responseData), "ReferenceResponseDataImpl") == 0) {
            // ReferenceResponseDataImpl �̏ꍇ�̂݁ACSV�o�͉ۂ�����{����
            if (!StringUtil::isEmpty($this->resultCsv)) {
                $rb = false;
            }
        } elseif (strcasecmp(get_class($this->responseData), "FilePaymentResponseDataImpl") == 0) {
            // �t�@�C�����ς͏��Result�̂�
            $rb = false;
        }

        return $rb;
    }

    /**
     * CSV �o�͔���
     *
     * @return boolean true=CSV Output false=Non
     */
    function isCSVOutput()
    {
        $rb = false;

        if ($this->masterFile->isTelegramKindRef($this->telegramKind)
            && !StringUtil::isEmpty($this->resultCsv)
        ) {
            // �d����ʂ��Ɖ� ���� ����CSV�t�@�C���� ���ݒ�ς̏ꍇ
            if ($this->getResultStatus() == PaygentB2BModule__RESULT_STATUS_ERROR) {
                // �������ʂ��ُ�̏ꍇ
                if ($this->getResponseCode() == PaygentB2BModule__RESPONSE_CODE_9003) {
                    // ���X�|���X�R�[�h�� 9003 �̏ꍇ
                    $rb = true;
                }
            } else {
                // �������ʂ�����̏ꍇ
                $rb = true;
            }
        }

        return $rb;
    }

    /**
     * �t�@�C�����ό��ʃt�@�C�� �o�͔���
     *
     * @return boolean true=CSV Output false=Non
     */
    function isFilePaymentOutput()
    {
        if (PaygentB2BModule__TELEGRAM_KIND_FILE_PAYMENT_RES == $this->telegramKind
            && !StringUtil::isEmpty($this->resultCsv)
        ) {
            return true;
        }
        return false;
    }

    /**
     * �d���v���p�����[�^ ���p�J�i �u������
     */
    function replaceTelegramKana()
    {

        foreach ($this->telegramParam as $keys => $values) {
            if (in_array(strtolower($keys), $this->REPLACE_KANA_PARAM)) {
                $this->telegramParam[$keys] =
                    StringUtil::convertKatakanaZenToHan($values);
            }
        }
    }

}

?>
