<?php
namespace Bluecom\Paygent\lib\jp\co\ks\merchanttool\connectmodule\exception;

/*
 * �ڑ����W���[���@�e��G���[�pException
 *
 * @version $Revision: 15878 $
 * @author $Author: orimoto $
 */

define("PaygentB2BModuleException__serialVersionUID", 1);

/**
 * �ݒ�t�@�C���Ȃ��G���[
 */
define("PaygentB2BModuleException__RESOURCE_FILE_NOT_FOUND_ERROR", "E01001");

/**
 * �ݒ�t�@�C���s���G���[
 */
define("PaygentB2BModuleException__RESOURCE_FILE_REQUIRED_ERROR", "E01002");

/**
 * ���̑��̃G���[
 */
define("PaygentB2BModuleException__OTHER_ERROR", "E01901");

/**
 * CSV�o�̓G���[
 */
define("PaygentB2BModuleException__CSV_OUTPUT_ERROR", "E01004");

/**
 * ����t�@�C���G���[
 */
define("PaygentB2BModuleException__FILE_PAYMENT_ERROR", "E01005");


class PaygentB2BModuleException
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
