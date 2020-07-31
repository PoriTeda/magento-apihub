<?php
namespace Bluecom\Paygent\lib\jp\co\ks\merchanttool\connectmodule\entity;

use Bluecom\Paygent\lib\jp\co\ks\merchanttool\connectmodule\exception\PaygentB2BModuleConnectExceptionp;
use Bluecom\Paygent\lib\jp\co\ks\merchanttool\connectmodule\exception\PaygentB2BModuleException;
use Bluecom\Paygent\lib\jp\co\ks\merchanttool\connectmodule\util\CSVWriter;
use Bluecom\Paygent\lib\jp\co\ks\merchanttool\connectmodule\util\CSVTokenizer;
use Bluecom\Paygent\lib\jp\co\ks\merchanttool\connectmodule\util\HttpsRequestSender;
use Bluecom\Paygent\lib\jp\co\ks\merchanttool\connectmodule\util\StringUtil;
use Bluecom\Paygent\lib\jp\co\ks\merchanttool\connectmodule\entity\ResponseData;

/**
 * �t�@�C�����όn�����d�������N���X
 *
 * @version $Revision: 15878 $
 * @author $Author: orimoto $
 */

/**
 * �s�ԍ��i�w�b�_�[���j= "1"
 */
define("FilePaymentResponseDataImpl__LINENO_HEADER", "1");

/**
 * ���R�[�h�敪 �ʒu", 0
 */
define("FilePaymentResponseDataImpl__LINE_RECORD_DIVISION", 0);

/**
 * �w�b�_�[�� �������� �ʒu 6
 */
define("FilePaymentResponseDataImpl__LINE_HEADER_RESULT", 6);

/**
 * �w�b�_�[�� ���X�|���X�R�[�h �ʒu", 7
 */
define("FilePaymentResponseDataImpl__LINE_HEADER_RESPONSE_CODE", 7);

/**
 * �w�b�_�[�� ���X�|���X�ڍ� �ʒu", 8
 */
define("FilePaymentResponseDataImpl__LINE_HEADER_RESPONSE_DETAIL", 8);

/**
 * ���s����
 */
define("FilePaymentResponseDataImpl__LINE_SEPARATOR", "\r\n");

class FilePaymentResponseDataImpl extends ResponseData
{

    /** �������� */
    var $resultStatus;

    /** ���X�|���X�R�[�h */
    var $responseCode;

    /** ���X�|���X�ڍ� */
    var $responseDetail;

    /**
     * �t�@�C�����ς̏ꍇ�͒l���܂ރp�[�X�͕s�B
     * ���Exception��throw����B
     *
     * @param data
     */
    function parse($body)
    {
        trigger_error(PaygentB2BModuleException__FILE_PAYMENT_ERROR
            . ": parse is not supported.", E_USER_WARNING);
        return PaygentB2BModuleException__FILE_PAYMENT_ERROR;
    }

    /**
     * data �𕪉� ���U���g���̂݁A�ϐ��ɐݒ�B
     *
     * @param body
     * @return mixed TRUE:�����A���F�G���[�R�[�h
     */
    function parseResultOnly($body)
    {

        $csvTknzr = new CSVTokenizer(CSVTokenizer__DEF_SEPARATOR,
            CSVTokenizer__NO_ITEM_ENVELOPE);
        $line = "";

        // ���U���g���̏�����
        $this->resultStatus = "";
        $this->responseCode = "";
        $this->responseDetail = "";

        $lines = explode(FilePaymentResponseDataImpl__LINE_SEPARATOR, $body);
        foreach ($lines as $i => $line) {
            $lineItem = $csvTknzr->parseCSVData($line);

            if (0 < count($lineItem)) {
                if ($lineItem[FilePaymentResponseDataImpl__LINE_RECORD_DIVISION]
                    == FilePaymentResponseDataImpl__LINENO_HEADER
                ) {
                    // �w�b�_�[���̍s�̏ꍇ
                    if (FilePaymentResponseDataImpl__LINE_HEADER_RESULT < count($lineItem)) {
                        // �������ʂ�ݒ�
                        $this->resultStatus = $lineItem[FilePaymentResponseDataImpl__LINE_HEADER_RESULT];
                    }
                    if (FilePaymentResponseDataImpl__LINE_HEADER_RESPONSE_CODE < count($lineItem)) {
                        // ���X�|���X�R�[�h��ݒ�
                        $this->responseCode = $lineItem[FilePaymentResponseDataImpl__LINE_HEADER_RESPONSE_CODE];
                    }
                    if (FilePaymentResponseDataImpl__LINE_HEADER_RESPONSE_DETAIL < count($lineItem)) {
                        // ���X�|���X�ڍׂ�ݒ�
                        $this->responseDetail = $lineItem[FilePaymentResponseDataImpl__LINE_HEADER_RESPONSE_DETAIL];
                    }

                    // �w�b�_�[�݂̂̉�͂ŏI��
                    break;
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
     * ���̃f�[�^���擾�B
     *
     * @return Map
     */
    function resNext()
    {
        return null;
    }

    /**
     * ���̃f�[�^�����݂��邩����B
     *
     * @return boolean true=���݂��� false=���݂��Ȃ�
     */
    function hasResNext()
    {
        return false;
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

    /**
     * CSV ���쐬
     *
     * @param resBody
     * @param resultCsv String
     * @return boolean true�F�����A���F�G���[�R�[�h
     */
    function writeCSV($body, $resultCsv)
    {
        $rb = false;

        // CSV �� 1�s���o��
        $csvWriter = new CSVWriter($resultCsv);
        if ($csvWriter->open() === false) {
            // �t�@�C���I�[�v���G���[
            trigger_error(PaygentB2BModuleException__CSV_OUTPUT_ERROR
                . ": Failed to open CSV file.", E_USER_WARNING);
            return PaygentB2BModuleException__CSV_OUTPUT_ERROR;
        }

        $lines = explode(FilePaymentResponseDataImpl__LINE_SEPARATOR, $body);

        foreach ($lines as $i => $line) {
            if (StringUtil::isEmpty($line)) {
                continue;
            }
            if (!$csvWriter->writeOneLine($line)) {
                // �������߂Ȃ������ꍇ
                trigger_error(PaygentB2BModuleException__CSV_OUTPUT_ERROR
                    . ": Failed to write to CSV file.", E_USER_WARNING);
                return PaygentB2BModuleException__CSV_OUTPUT_ERROR;
            }
        }

        $csvWriter->close();

        $rb = true;

        return $rb;
    }


}

?>