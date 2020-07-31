<?php
namespace Bluecom\Paygent\lib\jp\co\ks\merchanttool\connectmodule\exception;

/*
 * �ڑ����W���[���@�ڑ��G���[�pException
 *
 * @version $Revision: 15878 $
 * @author $Author: orimoto $
 */


define("PaygentB2BModuleConnectException__serialVersionUID", 1);

/**
 * ���W���[���p�����[�^�G���[
 */
define("PaygentB2BModuleConnectException__MODULE_PARAM_REQUIRED_ERROR", "E02001");

/**
 * �d���v���p�����[�^�G���[
 */
define("PaygentB2BModuleConnectException__TEREGRAM_PARAM_REQUIRED_ERROR", "E02002");

/**
 * �d���v���p�����[�^�Œ�l�z��O�G���[
 */
define("PaygentB2BModuleConnectException__TEREGRAM_PARAM_OUTSIDE_ERROR", "E02003");

/**
 * �ؖ����G���[
 */
define("PaygentB2BModuleConnectException__CERTIFICATE_ERROR", "E02004");

/**
 * ���σZ���^�[�ڑ��G���[
 */
define("PaygentB2BModuleConnectException__KS_CONNECT_ERROR", "E02005");

/**
 * �����Ή���ʃG���[
 */
define("PaygentB2BModuleConnectException__RESPONSE_TYPE_ERROR", "E02007");


class PaygentB2BModuleConnectException
{

    /** �G���[�R�[�h */
    var $errorCode = "";

    /**
     * �R���X�g���N�^
     *
     * @param errorCode String
     * @param msg String
     */
    function __construct($errCode, $msg = null)
    {
        $this->errorCode = $errCode;
    }

    /**
     * �G���[�R�[�h��Ԃ�
     *
     * @return String errorCode
     */
    function getErrorCode()
    {
        return $this->errorCode;
    }

    /**
     * ���b�Z�[�W��Ԃ�
     *
     * @return String code=message
     */
    function getLocalizedMessage()
    {
    }

}

?>
