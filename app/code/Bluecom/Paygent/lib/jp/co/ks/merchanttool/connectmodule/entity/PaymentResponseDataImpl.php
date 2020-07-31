<?php
namespace Bluecom\Paygent\lib\jp\co\ks\merchanttool\connectmodule\entity;

use Bluecom\Paygent\lib\jp\co\ks\merchanttool\connectmodule\exception\PaygentB2BModuleConnectException;
use Bluecom\Paygent\lib\jp\co\ks\merchanttool\connectmodule\exception\PaygentB2BModuleException;
use Bluecom\Paygent\lib\jp\co\ks\merchanttool\connectmodule\util\HttpsRequestSender;
use Bluecom\Paygent\lib\jp\co\ks\merchanttool\connectmodule\util\StringUtil;
use Bluecom\Paygent\lib\jp\co\ks\merchanttool\connectmodule\entity\ResponseData;

/**
 * ���όn�����d�������N���X
 *
 * @version $Revision: 15878 $
 * @author $Author: orimoto $
 */

/**
 * �����d���p��؂蕶��
 */
define("PaymentResponseDataImpl__PROPERTIES_REGEX", "=");

/**
 * �����d���p��؂萔
 */
define("PaymentResponseDataImpl__PROPERTIES_REGEX_COUNT", 2);

/**
 * ���s����
 */
define("PaymentResponseDataImpl__LINE_SEPARATOR", "\r\n");


class PaymentResponseDataImpl extends ResponseData
{

    /** �������� ������*/
    var $resultStatus;

    /** ���X�|���X�R�[�h ������*/
    var $responseCode;

    /** ���X�|���X�ڍ� */
    var $responseDetail;

    /** �f�[�^ array*/
    var $data;

    /** ���݂�Index */
    var $currentIndex;

    /**
     * �R���X�g���N�^
     */
    function __construct()
    {
        $this->data = array();
        $this->currentIndex = 0;
    }

    /**
     * body �𕪉�
     *
     * @param ���X�|���X�{�f�B
     * @return boolean TRUE: �����A���F�G���[�R�[�h
     */
    function parse($body)
    {

        $line = "";
        // �ێ��f�[�^��������
        $this->data = array();
        $map = array();

        // ���݈ʒu��������
        $this->currentIndex = 0;

        // ���U���g���̏�����
        $this->resultStatus = "";
        $this->responseCode = "";
        $this->responseDetail = "";

        // "_html" �L�[���݃t���O
        $htmlKeyFlg = false;

        // "_htmk" �L�[�l
        $htmlKey = "";

        // "_html" �L�[�o���Ȍ�̃f�[�^�ێ�
        $htmlValue = "";

        $lines = explode(PaymentResponseDataImpl__LINE_SEPARATOR, $body);

        foreach ($lines as $i => $line) {
            $lineItem = StringUtil::split($line, PaymentResponseDataImpl__PROPERTIES_REGEX,
                PaymentResponseDataImpl__PROPERTIES_REGEX_COUNT);

            // �Ǎ��I��
            $tmpLen = strlen($lineItem[0]) - strlen(ResponseData__HTML_ITEM);
            if ($tmpLen >= 0
                && strpos($lineItem[0], ResponseData__HTML_ITEM, $tmpLen)
                === $tmpLen
            ) {
                // Key �� "_html" �̏ꍇ
                $htmlKey = $lineItem[0];
                $htmlKeyFlg = true;
            }
            if ($htmlKeyFlg) {
                if (!(strlen($lineItem[0]) - strlen(ResponseData__HTML_ITEM) >= 0
                    && strpos($lineItem[0], ResponseData__HTML_ITEM,
                        strlen($lineItem[0]) - strlen(ResponseData__HTML_ITEM))
                    === strlen($lineItem[0]) - strlen(ResponseData__HTML_ITEM))
                ) {
                    // "_html" Key ���ǂݎ��ꂽ�ꍇ
                    $htmlValue .= $line;
                    $htmlValue .= PaymentResponseDataImpl__LINE_SEPARATOR;
                }
            } else {
                if (1 < count($lineItem)) {
                    if ($lineItem[0] == ResponseData__RESULT) {
                        // �������ʂ�ݒ�
                        $this->resultStatus = $lineItem[1];
                    } else if ($lineItem[0] == ResponseData__RESPONSE_CODE) {
                        // ���X�|���X�R�[�h��ݒ�
                        $this->responseCode = $lineItem[1];
                    } else if ($lineItem[0] == ResponseData__RESPONSE_DETAIL) {
                        // ���X�|���X�ڍׂ�ݒ�
                        $this->responseDetail = $lineItem[1];
                    } else {
                        // Map�ɐݒ�
                        $map[$lineItem[0]] = $lineItem[1];
                    }
                }
            }
        }

        if ($htmlKeyFlg) {
            // "_html" Key ���o�������ꍇ�A�ݒ�
            if (strlen(PaymentResponseDataImpl__LINE_SEPARATOR) <= strlen($htmlValue)) {
                if (strpos($htmlValue, PaymentResponseDataImpl__LINE_SEPARATOR,
                        strlen($htmlValue) - strlen(PaymentResponseDataImpl__LINE_SEPARATOR))
                    === strlen($htmlValue) - strlen(PaymentResponseDataImpl__LINE_SEPARATOR)
                ) {
                    $htmlValue = substr($htmlValue, 0,
                        strlen($htmlValue) - strlen(PaymentResponseDataImpl__LINE_SEPARATOR));
                }
            }
            $map[$htmlKey] = $htmlValue;
        }

        if (0 < count($map)) {
            // Map ���ݒ肳��Ă���ꍇ
            $this->data[] = $map;
        }

        if (StringUtil::isEmpty($this->resultStatus)) {
            // �������ʂ� �󕶎� �������� null �̏ꍇ
            trigger_error(PaygentB2BModuleConnectException__KS_CONNECT_ERROR
                . ": resultStatus is Nothing.", E_USER_WARNING);
            return PaygentB2BModuleConnectException__KS_CONNECT_ERROR;
        }

        return true;
    }

    /**
     * data �𕪉� ���U���g���̂݁A�ϐ��ɔ��f
     *
     * @param data
     * @return boolean TRUE: �����AFALSE�F���s
     */
    function parseResultOnly($body)
    {

        $line = "";

        // �ێ��f�[�^��������
        $this->data = array();

        // ���݈ʒu��������
        $this->currentIndex = 0;

        // ���U���g���̏�����
        $this->resultStatus = "";
        $this->responseCode = "";
        $this->responseDetail = "";

        $lines = explode(PaymentResponseDataImpl__LINE_SEPARATOR, $body);
        foreach ($lines as $i => $line) {
            $lineItem = StringUtil::split($line, PaymentResponseDataImpl__PROPERTIES_REGEX);
            // �Ǎ��I��
            if (strpos($lineItem[0], ResponseData__HTML_ITEM)
                === strlen($lineItem[0]) - strlen(ResponseData__HTML_ITEM)
            ) {
                // Key �� "_html" �̏ꍇ
                break;
            }

            if (1 < count($lineItem)) {
                // 1�s���Ǎ�(���ڐ���2�ȏ�̏ꍇ)
                if ($lineItem[0] == ResponseData__RESULT) {
                    // �������ʂ�ݒ�
                    $this->resultStatus = $lineItem[1];
                } else if ($lineItem[0] == ResponseData__RESPONSE_CODE) {
                    // ���X�|���X�R�[�h��ݒ�
                    $this->responseCode = $lineItem[1];
                } else if ($lineItem[0] == ResponseData__RESPONSE_DETAIL) {
                    // ���X�|���X�ڍׂ�ݒ�
                    $this->responseDetail = $lineItem[1];
                }
            }
        }

        if (StringUtil::isEmpty($this->resultStatus)) {
            // �������ʂ� �󕶎� �������� null �̏ꍇ
            trigger_error(PaygentB2BModuleConnectException__KS_CONNECT_ERROR
                . ": resultStatus is Nothing.", E_USER_WARNING);
            return PaygentB2BModuleConnectException__KS_CONNECT_ERROR;
        }
        return true;
    }

    /**
     * ���̃f�[�^���擾
     *
     * @return Map �f�[�^���Ȃ��ꍇ�ANULL��߂�
     */
    function resNext()
    {
        $map = null;

        if ($this->hasResNext()) {

            $map = $this->data[$this->currentIndex];

            $this->currentIndex++;
        }

        return $map;
    }

    /**
     * ���̃f�[�^�����݂��邩����
     *
     * @return boolean true=���݂��� false=���݂��Ȃ�
     */
    function hasResNext()
    {
        $rb = false;

        if ($this->currentIndex < count($this->data)) {
            $rb = true;
        }

        return $rb;
    }

    /**
     * resultStatus ���擾
     *
     * @return String
     */
    function getResultStatus()
    {
        return $this->resultStatus;
    }

    /**
     * responseCode ���擾
     *
     * @return String
     */
    function getResponseCode()
    {
        return $this->responseCode;
    }

    /**
     * responseDetail ���擾
     *
     * @return String
     */
    function getResponseDetail()
    {
        return $this->responseDetail;
    }

}

?>