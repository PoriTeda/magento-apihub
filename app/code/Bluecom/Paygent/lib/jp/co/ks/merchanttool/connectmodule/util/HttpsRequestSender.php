<?php
namespace Bluecom\Paygent\lib\jp\co\ks\merchanttool\connectmodule\util;

use Bluecom\Paygent\lib\jp\co\ks\merchanttool\connectmodule\util\StringUtil;
use Bluecom\Paygent\lib\jp\co\ks\merchanttool\connectmodule\exception\PaygentB2BModuleConnectException;
use Bluecom\Paygent\lib\jp\co\ks\merchanttool\connectmodule\exception\PaygentB2BModuleException;
use Bluecom\Paygent\lib\jp\co\ks\merchanttool\connectmodule\util\PaygentB2BModuleLogger;

/**
 * https�v���������Ȃ����[�e�B���e�B�N���X�B
 *
 * @vesrion $Revision: 34014 $
 * @author $Author: orimoto $
 */

	// cURL �G���[�R�[�h
	// http://curl.haxx.se/libcurl/c/libcurl-errors.html
	define("HttpsRequestSender__CURLE_COULDNT_CONNECT", 7);
	define("HttpsRequestSender__CURLE_SSL_CERTPROBLEM", 58);
	define("HttpsRequestSender__CURLE_SSL_CACERT", 60);
	define("HttpsRequestSender__CURLE_SSL_CACERT_BADFILE", 77);
	define("HttpsRequestSender__CURLE_HTTP_RETURNED_ERROR", 22);

	/**
	 * HTTP POST �ʐM�p�Œ�l
	 */
	define("HttpsRequestSender__POST", "POST");

	/**
	 * HTTP�v���g�R����\���萔
	 */
	define("HttpsRequestSender__HTTP", "HTTP");

	/**
	 * HTTP/1.0��\���萔
	 */
	define("HttpsRequestSender__HTTP_1_0", "HTTP/1.0");

	/**
	 * HTTP�ʐM�̐����R�[�h
	 */
	define("HttpsRequestSender__HTTP_1_0_200", "HTTP/1.0 200");

	/**
	 * HTTP�ʐM�̐����R�[�h�F200
	 */
	define("HttpsRequestSender__HTTP_SUCCESS", 200);

	/**
	 * HTTP�ʐM�̐����R�[�h�F206
	 */
	define("HttpsRequestSender__HTTP_PARTIAL_CONTENT", 206);

	/**
	 * �d����
	 */
	define("HttpsRequestSender__TELEGRAM_LENGTH", 10240);

	/**
	 * HTTPS Default Port
	 */
	define("HttpsRequestSender__DEFAULT_PORT", 443);

	/**
	 * ���N�G�X�g�E���X�|���X�̉��s�R�[�h
	 */
	define("HttpsRequestSender__CRLF", "\r\n");

	/**
	 * �f�t�H���g�̃G���R�[�f�B���O
	 */
	define("HttpsRequestSender__DEFAULT_ENCODING", "SJIS-win");

	/**
	 * HTTP�X�e�[�^�X�R�[�h�ϐ��̏����l
	 */
	define("HttpsRequestSender__HTTP_STATUS_INIT_VALUE", -1);

	/**
	 * �X�e�[�^�X�R�[�h�̒���
	 */
	define("HttpsRequestSender__REGEXPSTATUS_LEN", 3);

	/**
	 * Content-Length
	 */
	define("HttpsRequestSender__CONTENT_LENGTH", "Content-Length");

	/**
	 * User-Agent
	 */
	define("HttpsRequestSender__USER_AGENT", "User-Agent");

	/**
	 * Content-Type
	 */
	define("HttpsRequestSender__CONTENT_TYPE", "Content-Type=application/x-www-form-urlencoded");
	define("HttpsRequestSender__HTTP_ENCODING", "charset=Windows-31J");

	/**
	 * �}�X�N����
	 */
	define("HttpsRequestSender__MASK_STRING", "X");

class HttpsRequestSender {
	/**
	 * KeyStore Password
	 */
	var $KEYSTORE_PASSWORD = "changeit";

	/** ���X�|���X�w�b�_ */
	var $responseHeader;

    /**
     * @var null
     */
	var $responseBody;

	/** �X�e�[�^�X�R�[�h�@*/
	var $statusCode;

	/** �ڑ��� URL */
	var $url;

	/** �N���C�A���g�ؖ����p�X */
	var $clientCertificatePath;

	/** �F�؋Ǐؖ����p�X */
	var $caCertificatePath;

	/** SSL�ʐM�p�\�P�b�g */
	var $ch;

	/** �g���l���\�P�b�g */
	//var $tunnelSocket;

	/** �^�C���A�E�g�l int */
	var $timeout;

	/** Proxy�z�X�g�� */
	var $proxyHostName;

	/** Proxy�|�[�g�ԍ� int */
	var $proxyPort;

	/** Proxy�ڑ��^�C���A�E�g�l */
	var $proxyConnectTimeout;

	/** Proxy�`���^�C���A�E�g�l */
	var $proxyCommunicateTimeout;

	/** Proxy�g�p���� */
	var $isUsingProxy = false;

	/** �f�o�b�O���O�}�X�N�Ώۍ��� */
	var $MASK_COLUMNS = array("card_number", "card_conf_number", 'connect_password');

	/** �������ʃ��b�Z�[�W */
	var $resultMessage = '';

	/**
	 * �R���X�g���N�^<br>
	 * �ڑ���URL��ݒ�
	 *
	 * @param url String
	 */
	function __construct($url) {
		$this->url = $url;
		$this->proxyHostName = "";
		$this->proxyPort = 0;

		$this->responseBody = null;
		$this->responseHeader = null;
	}

	/**
	 * �N���C�A���g�ؖ����p�X��ݒ�
	 *
	 * @param fileName String
	 */
	function setClientCertificatePath($fileName) {
		$this->clientCertificatePath = $fileName;
	}

	/**
	 * �F�؋Ǐؖ����p�X��ݒ�
	 *
	 * @param fileName String
	 */
	function setCaCertificatePath($fileName) {
		$this->caCertificatePath = $fileName;
	}

	/**
	 * �^�C���A�E�g��ݒ�
	 *
	 * @param timeout int
	 */
	function setTimeout($timeout) {
		$this->timeout = $timeout;
	}

	/**
	 * Proxy�ڑ��^�C���A�E�g��ݒ�
	 *
	 * @param proxyConnectTimeout int
	 */
	function setProxyConnectTimeout($proxyConnectTimeout) {
		$this->proxyConnectTimeout = $proxyConnectTimeout;
	}

	/**
	 * Proxy�`���^�C���A�E�g��ݒ�
	 *
	 * @param proxyCommunicateTimeout int
	 */
	function setProxyCommunicateTimeout($proxyCommunicateTimeout) {
		$this->proxyCommunicateTimeout = $proxyCommunicateTimeout;
	}

	/**
	 * ProxyHostName, ProxyPort ��ݒ�
	 *
	 * @param proxyHostName String
	 * @param proxyPort int
	 */
	function setProxyInfo($proxyHostName, $proxyPort) {
		$this->proxyHostName = $proxyHostName;
		$this->proxyPort = $proxyPort;
		$this->isUsingProxy = false;

		if (!StringUtil::isEmpty($this->proxyHostName) && 0 < $this->proxyPort) {
			// Proxy��񂪐ݒ肳�ꂽ�ׁAtrue ��ݒ�
			$this->isUsingProxy = true;
		}
	}

	/**
	 * �������ʃ��b�Z�[�W
	 *
	 * @return resultMessage String
	 */
	function getResultMessage() {
		return $this->resultMessage;
	}

	/**
	 * Post�����{
	 *
	 * @param formData Map
	 * @param debugFlg
	 * @return mixed TRUE:�����A��:�G���[�R�[�h
	 */
	function postRequestBody($formData, $debugFlg) {

		// �ʐM�J�n
		$this->initCurl();

		if ($this->isUsingProxy) {
			// �v���L�V�o�R�ŒʐM��ɐڑ�
			$this->setProxy();
		}

		// ���N�G�X�g�𑗐M
		$retCode = $this->send($formData, $debugFlg);

		// ���X�|���X����M
		$this->closeCurl();

		return $retCode;
	}

	/**
	 * ��M�f�[�^��Ԃ�
	 *
	 * @return InputStream
	 */
	function getResponseBody() {
		return $this->responseBody;
	}

	/**
	 * �d�����擾
	 *
	 * @return telegramLength(byte)
	 */
	function getTelegramLength($formData) {
		if ($formData == null) {
			return 0;
		}

		$sb = $this->url;
		$sb .= "?";

		foreach($formData as $key => $value) {
			$sb .= $key;
			$sb .= "=";
			$sb .= $value;
			$sb .= "&";
		}

		$rs = "";

		if (0 < strlen($sb)) {
			$rs = substr($sb, 0, strlen($sb) - 1);
		}

		return strlen($rs);
	}

	/**
	 * �v���d�����쐬
	 *
	 * @param formData Map �v���d��
	 * @param debugLogFlg �f�o�b�O���O�t���O
	 * @return String �쐬�����v���d���iURL�j
	 */
	function convertToUrlEncodedString($formData, $debugLogFlg) {
		$encodedString = "";
		if ($formData == null) {
			return "";
		}

		foreach($formData as $key => $value) {
//			$this->outputDebugLog("param: " . $key . " = \"" . $value . "\"");

			if ($debugLogFlg and in_array($key, $this->MASK_COLUMNS) and !StringUtil::isEmpty($value)) {
				// �f�o�b�O���O�o�͎��A�}�X�N�Ώۍ��ڂ̏ꍇ�͉�1���ȊO���}�X�N�����ŏo�͂���
				$value = str_repeat(HttpsRequestSender__MASK_STRING, strlen($value) - 1) . substr($value, -1);
			}

			$tmp = $key;
			$encodedString .= urlencode($tmp);
			$encodedString .= "=";
			$tmp = $value;
			$encodedString .= urlencode($tmp);
			$encodedString .= "&";
		}

		$rs = "";

		if (0 < strlen($encodedString)) {
			$rs = substr($encodedString, 0, strlen($encodedString) - 1);
		}

		return $rs;

	}

	/**
	 * �f�o�b�O���O�o�̓��\�b�h
	 * ���O�o�̓N���X�̃C���X�^���X�����Ɏ��s������W���o�͂ɃG���[���b�Z�[�W��
	 * �o�͂���B
	 *
	 * @param msg String �o�̓��b�Z�[�W
	 */
	function outputDebugLog($msg) {
		if(StringUtil::isEmpty($msg)) return;

		$inst = PaygentB2BModuleLogger::getInstance();
		if (is_object($inst)) {
			$inst->debug(get_class($this), $msg);
		}
	}

	/**
	 * Proxy�ڑ��p
	 *
	 */
	function setProxy() {
		curl_setopt($this->ch, CURLOPT_HTTPPROXYTUNNEL, true);
		curl_setopt($this->ch, CURLOPT_PROXY, "http://" . $this->proxyHostName . ":" . $this->proxyPort);

	}

	/**
	 * �ڑ��̂��߂̏���������
	 *
	 */
	function initCurl() {
		$rslt = true;
		// ������
		$this->ch = curl_init($this->url);

		$rslt = $rslt && curl_setopt($this->ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0 );
		$rslt = $rslt && curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
		$rslt = $rslt && curl_setopt($this->ch, CURLOPT_POST, true);
		$rslt = $rslt && curl_setopt($this->ch, CURLOPT_HEADER, true);

		// �ؖ���
		$rslt = $rslt && curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, true);
		$rslt = $rslt && curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, false);
		$rslt = $rslt && curl_setopt($this->ch, CURLOPT_SSLCERT, $this->clientCertificatePath);
		$rslt = $rslt && curl_setopt($this->ch, CURLOPT_SSLKEYPASSWD, $this->KEYSTORE_PASSWORD);
		$rslt = $rslt && curl_setopt($this->ch, CURLOPT_CAINFO, $this->caCertificatePath);

		// �^�C���A�E�g
		$rslt = $rslt && curl_setopt($this->ch, CURLOPT_TIMEOUT, $this->timeout);
		$rslt = $rslt && curl_setopt($this->ch, CURLOPT_CONNECTTIMEOUT, $this->proxyConnectTimeout);

		return $rslt;
	}

	/**
	 * ���N�G�X�g�����Ƒ��M
	 *
	 * @param formData Map �v���d��
	 * @param debugFlg �f�o�b�O�t���O
	 * @return mixed TRUE:�����A��:�G���[�R�[�h
	 */
	function send($formData, $debugFlg) {
		// ���N�G�X�g�� Map ���� String �ɕϊ�
		$debugFlg=true;
		$query = $this->convertToUrlEncodedString($formData, false);

		$header = array();
		$header[] = HttpsRequestSender__CONTENT_TYPE;
		$header[] = HttpsRequestSender__HTTP_ENCODING;
		$header[] = HttpsRequestSender__CONTENT_LENGTH . ": "
			. (StringUtil::isEmpty($query)? "0" : strlen($query));
		$header[] = HttpsRequestSender__USER_AGENT . ": " . "curl_php";
		curl_setopt($this->ch, CURLOPT_HTTPHEADER, $header);
		curl_setopt($this->ch, CURLOPT_POSTFIELDS, $query);

		// ���N�G�X�g���e ���O�o��
		if ($debugFlg) {
			$debugQuery = $this->convertToUrlEncodedString($formData, true);
			$this->outputDebugLog("request: " . $debugQuery);
		}

		$str = curl_exec($this->ch);

		if ($str === false && curl_errno($this->ch) != 0) {
			return $this->procError();
		}

		$data = $str;

		$retCode = $this->parseResponse($data);

		// ���X�|���X���e ���O�o��
		if ($debugFlg) {
			$this->outputDebugLog("response: \r\n" . $this->responseBody);
		}

		return $retCode;
	}

	/**
	 * Curl�̃G���[����
	 * @return mixed True:���Ȃ��A���F�G���[�R�[�h
	 */
	function procError() {
		$errorNo = curl_errno($this->ch);
		$errorMsg = $errorNo . ": " . curl_error($this->ch);
		$retCode = true;

		if ($errorNo <= HttpsRequestSender__CURLE_COULDNT_CONNECT) { // 7
			// �ڑ����
			$retCode = PaygentB2BModuleConnectException__KS_CONNECT_ERROR;
			$this->outputDebugLog($errorMsg);
		} else if ($errorNo == HttpsRequestSender__CURLE_COULDNT_CONNECT) { // 7
			// �ڑ����
			$retCode = PaygentB2BModuleConnectException__KS_CONNECT_ERROR;
			$this->outputDebugLog($errorMsg);
		} else if ($errorNo == HttpsRequestSender__CURLE_SSL_CERTPROBLEM) {
			// �F�ؖ��
			$retCode = PaygentB2BModuleConnectException__CERTIFICATE_ERROR;
			$this->outputDebugLog($errorMsg);
		} else if ($errorNo == HttpsRequestSender__CURLE_SSL_CACERT) {
			// �F�ؖ��
			$retCode = PaygentB2BModuleConnectException__CERTIFICATE_ERROR;
			$this->outputDebugLog($errorMsg);
		} else if ($errorNo == HttpsRequestSender__CURLE_SSL_CACERT_BADFILE) {	// CURLE_SSL_CACERT_BADFILE
			// �F�ؖ��
			$retCode = PaygentB2BModuleConnectException__CERTIFICATE_ERROR;
			$this->outputDebugLog($errorMsg);
		} else if ($errorNo == HttpsRequestSender__CURLE_HTTP_RETURNED_ERROR) {
			// HTTP Return code error
			$retCode = PaygentB2BModuleConnectException__KS_CONNECT_ERROR;
			$this->outputDebugLog($errorMsg);
		} else {
			// ���̑��̃G���[
			$retCode = PaygentB2BModuleConnectException__KS_CONNECT_ERROR;
			$this->outputDebugLog($errorMsg);
		}
		$this->resultMessage = "$retCode: $errorMsg";

		// �ؖ����t�@�C���̏�ԃ`�F�b�N
		foreach (array($this->clientCertificatePath, $this->caCertificatePath) as $path) {
			if (!file_exists($path)) {
				$this->resultMessage .= "(file is not exists: $path)";
			} elseif (!is_readable($path)) {
				$this->resultMessage .= "(file is not readable: $path)";
			}
		}

		trigger_error("$retCode: Http request ended with errors.", E_USER_WARNING);
		return $retCode;
	}

	/**
	 * ���X�|���X����M�B
	 *
	 * @param $data ���X�|���X������
	 * @return mixed TRUE:�����A��:�G���[�R�[�h
	 */
	function parseResponse($data) {

		// ���X�|���X��M
		$line = null;
		$retCode = HttpsRequestSender__HTTP_STATUS_INIT_VALUE;
		$bHeaderOver = false;
		$resBodyStart = 0;
		$lines = preg_split("/\r\n|\n|\r/", $data);

		// �w�b�_�܂ł�ǂݍ���
		foreach($lines as $i => $line) {

			if (StringUtil::isEmpty($line)) {
				 break;
			}
			$resBodyStart += strlen($line) + strlen(HttpsRequestSender__CRLF);

			if ($retCode === HttpsRequestSender__HTTP_STATUS_INIT_VALUE) {
				// �X�e�[�^�X�̉��
				$retCode = $this->parseStatusLine($line);
				$this->outputDebugLog("retCode ".$retCode);
				if ($retCode === true) {
					continue;
				}
				$this->outputDebugLog("Cannot get http return code.");
				return $retCode;
			}

			// �w�b�_�̉��
			if (!$this->parseResponseHeader($line)) {
				continue;
			}
		}
		$info = curl_getinfo($this->ch);
		// linux�T�[�o��header_size�Ɍ�����l���ݒ肳��鎖�ۂ�����ꂽ����size_download�ŃL���v�`�����Ă���
		$resBodyStart = -($info['size_download']);
		$this->responseBody = substr($data, $resBodyStart);

		return true;
	}

	/**
	 * �X�e�[�^�X���C�������
	 * (HTTP-Version SP Status-Code SP Reason-Phrase CRLF)
	 *
	 * @param line String �X�e�[�^�X���C��
	 * @return mixed TRUE:�����A��:�G���[�R�[�h
	 */
	function parseStatusLine($line) {

		if (StringUtil::isEmpty($line)) {

			// �s���ȃX�e�[�^�X�R�[�h���󂯎����
			return PaygentB2BModuleConnectException__KS_CONNECT_ERROR;
		}

		$statusLine = StringUtil::split($line, " ", 3);

		if (StringUtil::isNumeric($statusLine[1])) {
			$this->statusCode = intVal($statusLine[1]);
		} else {

			// �s���ȃX�e�[�^�X�R�[�h���󂯎����
			return PaygentB2BModuleConnectException__KS_CONNECT_ERROR;
		}

		if (strpos($statusLine[0], HttpsRequestSender__HTTP . "/") != 0
				|| !StringUtil::isNumericLength($statusLine[1], HttpsRequestSender__REGEXPSTATUS_LEN)) {

			// �s���ȃX�e�[�^�X�R�[�h���󂯎����
			return PaygentB2BModuleConnectException__KS_CONNECT_ERROR;
		}

		if (!((HttpsRequestSender__HTTP_SUCCESS <= $this->statusCode)
			&& ($this->statusCode <= HttpsRequestSender__HTTP_PARTIAL_CONTENT))) {

			// HTTP Status �� Success Code (200 - 206) �łȂ��ꍇ
			return PaygentB2BModuleConnectException__KS_CONNECT_ERROR;
		}

		return true;
	}

	/**
	 * ���X�|���X�w�b�_����s��͂��āA�����Ɋi�[�B<br>
	 * ���X�|���X�w�b�_�̒l�����݂��Ȃ��ꍇ�́Anull��ݒ�B
	 *
	 * @param line String �T�[�o����󂯎�������X�|���X�s
	 * @return boolean true=�w�b�_��́E�i�[����, false=�w�b�_�ł͂Ȃ��i�w�b�_���I���j
	 */
	function parseResponseHeader($line) {
		if (StringUtil::isEmpty($line)) {
			// HEADER�I��
			return false;
		}

		// HEADER
		$headerStr = StringUtil::split($line, ":", 2);
		if ($this->responseHeader == null) {
			$this->responseHeader = array();
		}

		if (count($headerStr) == 1 || strlen(trim($headerStr[1])) == 0) {
			// �l�����݂��Ȃ� or �l���󕶎���
			$this->responseHeader[$headerStr[0]] = null;
		} else {
			$this->responseHeader[$headerStr[0]] = trim($headerStr[1]);
		}

		return true;
	}

	/**
	 * Close curl
	 *
	 */
	function closeCurl() {
		// �v���L�V�\�P�b�gCLOSE
		if ($this->ch != null) {
			curl_close($this->ch);
			$this->ch = null;
		}
	}

}

?>
