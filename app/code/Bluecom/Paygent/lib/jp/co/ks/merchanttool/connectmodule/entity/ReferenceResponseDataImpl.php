<?php
namespace Bluecom\Paygent\lib\jp\co\ks\merchanttool\connectmodule\entity;

use Bluecom\Paygent\lib\jp\co\ks\merchanttool\connectmodule\exception\PaygentB2BModuleConnectException;
use Bluecom\Paygent\lib\jp\co\ks\merchanttool\connectmodule\exception\PaygentB2BModuleException;
use Bluecom\Paygent\lib\jp\co\ks\merchanttool\connectmodule\util\CSVWriter;
use Bluecom\Paygent\lib\jp\co\ks\merchanttool\connectmodule\util\CSVTokenizer;
use Bluecom\Paygent\lib\jp\co\ks\merchanttool\connectmodule\util\HttpsRequestSender;
use Bluecom\Paygent\lib\jp\co\ks\merchanttool\connectmodule\util\StringUtil;
use Bluecom\Paygent\lib\jp\co\ks\merchanttool\connectmodule\entity\ResponseData;

/**
 * �Ɖ�n�����d�������N���X
 *
 * @version $Revision: 15878 $
 * @author $Author: orimoto $
 */


/**
 * �s�ԍ��i�w�b�_�[���j= "1"
 */
define("ReferenceResponseDataImpl__LINENO_HEADER", "1");

/**
 * �s�ԍ��i�f�[�^�w�b�_�[���j", "2"
 */
define("ReferenceResponseDataImpl__LINENO_DATA_HEADER", "2");

/**
 * �s�ԍ��i�f�[�^���j", "3"
 */
define("ReferenceResponseDataImpl__LINENO_DATA", "3");

/**
 * �s�ԍ��i�g���[���[���j", "4"
 */
define("ReferenceResponseDataImpl__LINENO_TRAILER", "4");

/**
 * ���R�[�h�敪 �ʒu", 0
 */
define("ReferenceResponseDataImpl__LINE_RECORD_DIVISION", 0);

/**
 * �w�b�_�[�� �������� �ʒu 1
 */
define("ReferenceResponseDataImpl__LINE_HEADER_RESULT", 1);

/**
 * �w�b�_�[�� ���X�|���X�R�[�h �ʒu", 2
 */
define("ReferenceResponseDataImpl__LINE_HEADER_RESPONSE_CODE", 2);

/**
 * �w�b�_�[�� ���X�|���X�ڍ� �ʒu", 3
 */
define("ReferenceResponseDataImpl__LINE_HEADER_RESPONSE_DETAIL", 3);

/**
 * �g���[���[�� �f�[�^���� �ʒu", 1
 */
define("ReferenceResponseDataImpl__LINE_TRAILER_DATA_COUNT", 1);

/**
 * ���s����
 */
define("ReferenceResponseDataImpl__LINE_SEPARATOR", "\r\n");

class ReferenceResponseDataImpl extends ResponseData
{
    /** �������� */
    var $resultStatus;

    /** ���X�|���X�R�[�h */
    var $responseCode;

    /** ���X�|���X�ڍ� */
    var $responseDetail;

    /** �f�[�^�w�b�_�[ */
    var $dataHeader;

    /** �f�[�^ */
    var $data;

    /** ���݂�Index */
    var $currentIndex;

    /**
     * �R���X�g���N�^
     */
    function __construct()
    {
        $this->dataHeader = array();
        $this->data = array();
        $this->currentIndex = 0;
    }

    /**
     * data �𕪉�
     *
     * @param data
     * @return mixed TRUE:�����A���F�G���[�R�[�h
     */
    function parse($body)
    {

        $csvTknzr = new CSVTokenizer(CSVTokenizer__DEF_SEPARATOR,
            CSVTokenizer__DEF_ITEM_ENVELOPE);

        // �ێ��f�[�^��������
        $this->data = array();

        // ���݈ʒu��������
        $this->currentIndex = 0;

        // ���U���g���̏�����
        $this->resultStatus = "";
        $this->responseCode = "";
        $this->responseDetail = "";

        $lines = explode(ReferenceResponseDataImpl__LINE_SEPARATOR, $body);
        foreach ($lines as $i => $line) {
            $lineItem = $csvTknzr->parseCSVData($line);

            if (0 < count($lineItem)) {
                if ($lineItem[ReferenceResponseDataImpl__LINE_RECORD_DIVISION]
                    == ReferenceResponseDataImpl__LINENO_HEADER
                ) {
                    // �w�b�_�[���̍s�̏ꍇ
                    if (ReferenceResponseDataImpl__LINE_HEADER_RESULT < count($lineItem)) {
                        // �������ʂ�ݒ�
                        $this->resultStatus = $lineItem[ReferenceResponseDataImpl__LINE_HEADER_RESULT];
                    }
                    if (ReferenceResponseDataImpl__LINE_HEADER_RESPONSE_CODE < count($lineItem)) {
                        // ���X�|���X�R�[�h��ݒ�
                        $this->responseCode = $lineItem[ReferenceResponseDataImpl__LINE_HEADER_RESPONSE_CODE];
                    }
                    if (ReferenceResponseDataImpl__LINE_HEADER_RESPONSE_DETAIL < count($lineItem)) {
                        // ���X�|���X�ڍׂ�ݒ�
                        $this->responseDetail = $lineItem[ReferenceResponseDataImpl__LINE_HEADER_RESPONSE_DETAIL];
                    }
                } else if ($lineItem[ReferenceResponseDataImpl__LINE_RECORD_DIVISION]
                    == ReferenceResponseDataImpl__LINENO_DATA_HEADER
                ) {
                    // �f�[�^�w�b�_�[���̍s�̏ꍇ
                    $this->dataHeader = array();

                    for ($i = 1; $i < count($lineItem); $i++) {
                        // �f�[�^�w�b�_�[��ݒ�i���R�[�h�敪�͏����j
                        $this->dataHeader[] = $lineItem[$i];
                    }
                } else if ($lineItem[ReferenceResponseDataImpl__LINE_RECORD_DIVISION]
                    == ReferenceResponseDataImpl__LINENO_DATA
                ) {
                    // �f�[�^���̍s�̏ꍇ
                    // �f�[�^�w�b�_�[�������ɓW�J�ς݂ł��鎖��z��
                    $map = array();

                    if (count($this->dataHeader) == (count($lineItem) - 1)) {
                        // �f�[�^�w�b�_�[���ƁA�f�[�^���ڐ��i���R�[�h�敪�����j�͈�v
                        for ($i = 1; $i < count($lineItem); $i++) {
                            // �Ή�����f�[�^�w�b�_�[�� Key �ɁAMap�֐ݒ�
                            $map[$this->dataHeader[$i - 1]] = $lineItem[$i];
                        }
                    } else {
                        // �f�[�^�w�b�_�[���ƁA�f�[�^���ڐ�����v���Ȃ��ꍇ
                        $sb = PaygentB2BModuleException__OTHER_ERROR . ": ";
                        $sb .= "Not Mutch DataHeaderCount=";
                        $sb .= "" . count($this->dataHeader);
                        $sb .= " DataItemCount:";
                        $sb .= "" . (count($lineItem) - 1);
                        trigger_error($sb, E_USER_WARNING);
                        return PaygentB2BModuleException__OTHER_ERROR;
                    }

                    if (0 < count($map)) {
                        // Map ���ݒ肳��Ă���ꍇ
                        $this->data[] = $map;
                    }
                } else if ($lineItem[ReferenceResponseDataImpl__LINE_RECORD_DIVISION]
                    == ReferenceResponseDataImpl__LINENO_TRAILER
                ) {
                    // �g���[���[���̍s�̏ꍇ
                    if (ReferenceResponseDataImpl__LINE_TRAILER_DATA_COUNT < count($lineItem)) {
                        // �f�[�^�T�C�Y
                    }
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
     * data �𕪉� ���U���g���̂݁A�ϐ��ɐݒ�
     *
     * @param body
     * @return mixed TRUE:�����A���F�G���[�R�[�h
     */
    function parseResultOnly($body)
    {

        $csvTknzr = new CSVTokenizer(CSVTokenizer__DEF_SEPARATOR,
            CSVTokenizer__DEF_ITEM_ENVELOPE);
        $line = "";

        // �ێ��f�[�^��������
        $this->data = array();

        // ���݈ʒu��������
        $this->currentIndex = 0;

        // ���U���g���̏�����
        $this->resultStatus = "";
        $this->responseCode = "";
        $this->responseDetail = "";

        $lines = explode(ReferenceResponseDataImpl__LINE_SEPARATOR, $body);
        foreach ($lines as $i => $line) {
            $lineItem = $csvTknzr->parseCSVData($line);

            if (0 < count($lineItem)) {
                if ($lineItem[ReferenceResponseDataImpl__LINE_RECORD_DIVISION]
                    == ReferenceResponseDataImpl__LINENO_HEADER
                ) {
                    // �w�b�_�[���̍s�̏ꍇ
                    if (ReferenceResponseDataImpl__LINE_HEADER_RESULT < count($lineItem)) {
                        // �������ʂ�ݒ�
                        $this->resultStatus = $lineItem[ReferenceResponseDataImpl__LINE_HEADER_RESULT];
                    }
                    if (ReferenceResponseDataImpl__LINE_HEADER_RESPONSE_CODE < count($lineItem)) {
                        // ���X�|���X�R�[�h��ݒ�
                        $this->responseCode = $lineItem[ReferenceResponseDataImpl__LINE_HEADER_RESPONSE_CODE];
                    }
                    if (ReferenceResponseDataImpl__LINE_HEADER_RESPONSE_DETAIL < count($lineItem)) {
                        // ���X�|���X�ڍׂ�ݒ�
                        $this->responseDetail = $lineItem[ReferenceResponseDataImpl__LINE_HEADER_RESPONSE_DETAIL];
                    }
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
     * @return Map
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

    /**
     * �f�[�^�������擾
     *
     * @param data InputStream
     * @return int -1:�G���[
     */
    function getDataCount($body)
    {
        $ri = 0;
        $strCnt = null;

        $csvTknzr = new CSVTokenizer(CSVTokenizer__DEF_SEPARATOR,
            CSVTokenizer__DEF_ITEM_ENVELOPE);
        $line = "";

        $lines = explode(ReferenceResponseDataImpl__LINE_SEPARATOR, $body);
        foreach ($lines as $i => $line) {
            $lineItem = $csvTknzr->parseCSVData($line);

            if (0 < count($lineItem)) {
                if ($lineItem[ReferenceResponseDataImpl__LINE_RECORD_DIVISION]
                    == ReferenceResponseDataImpl__LINENO_TRAILER
                ) {
                    // �g���[���[���̍s�̏ꍇ
                    if (ReferenceResponseDataImpl__LINE_TRAILER_DATA_COUNT < count($lineItem)) {
                        // �f�[�^�������擾 while���甲����
                        if (StringUtil::isNumeric($lineItem[ReferenceResponseDataImpl__LINE_TRAILER_DATA_COUNT])) {
                            $strCnt = $lineItem[ReferenceResponseDataImpl__LINE_TRAILER_DATA_COUNT];
                        }
                        break;
                    }
                }
            }
        }

        if ($strCnt != null && StringUtil::isNumeric($strCnt)) {
            $ri = intval($strCnt);
        } else {
            return PaygentB2BModuleException__OTHER_ERROR;        //�G���[
        }

        return $ri;
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
            // �t�@�C���I�[�u���G���[
            trigger_error(PaygentB2BModuleException__CSV_OUTPUT_ERROR
                . ": Failed to open CSV file.", E_USER_WARNING);
            return PaygentB2BModuleException__CSV_OUTPUT_ERROR;
        }

        $lines = explode(ReferenceResponseDataImpl__LINE_SEPARATOR, $body);
        foreach ($lines as $i => $line) {
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