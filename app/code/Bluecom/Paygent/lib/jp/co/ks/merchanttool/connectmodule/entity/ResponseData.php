<?php
namespace Bluecom\Paygent\lib\jp\co\ks\merchanttool\connectmodule\entity;

use Bluecom\Paygent\lib\jp\co\ks\merchanttool\connectmodule\exception\PaygentB2BModuleConnectException;
use Bluecom\Paygent\lib\jp\co\ks\merchanttool\connectmodule\exception\PaygentB2BModuleException;

/**
 * �����d�������p�C���^�[�t�F�[�X
 *
 * @version $Revision: 15878 $
 * @author $Author: orimoto $
 */

/**
 * ��������
 */
define("ResponseData__RESULT", "result");

/**
 * ���X�|���X�R�[�h
 */
define("ResponseData__RESPONSE_CODE", "response_code");

/**
 * ���X�|���X�ڍ�
 */
define("ResponseData__RESPONSE_DETAIL", "response_detail");

/**
 * HTML����
 */
define("ResponseData__HTML_ITEM", "_html");


class ResponseData
{

    /**
     * ��M�d���𕪉����A��������ɕێ�
     *
     * @param data ��M�d��
     * @return boolean TRUE: �����AFALSE�F���s
     */
    function parse($data)
    {
    }

    /**
     * ��M�d���𕪉��A�������ʁA���X�|���X�R�[�h�A���X�|���X�ڍׂ̂ݕێ�
     *
     * @param data ��M�d��
     * @return boolean TRUE: �����AFALSE�F���s
     */
    function parseResultOnly($data)
    {
    }

    /**
     * �������ʂ��擾
     *
     * @return String ��������
     */
    function getResultStatus()
    {
    }

    /**
     * ���X�|���X�R�[�h���擾
     *
     * @return String ���X�|���X�R�[�h
     */
    function getResponseCode()
    {
    }

    /**
     * ���X�|���X�ڍׂ��擾
     *
     * @return String ���X�|���X�ڍ�
     */
    function getResponseDetail()
    {
    }

    /**
     * ��M�d�����A1���R�[�h���擾
     *
     * @return Map 1���R�[�h���̏��;�Ȃ��ꍇ�ANULL��Ԃ�
     */
    function resNext()
    {
    }

    /**
     * ���̃��R�[�h�����݂��邩����
     *
     * @return boolean ���茋��
     */
    function hasResNext()
    {
    }

}

?>