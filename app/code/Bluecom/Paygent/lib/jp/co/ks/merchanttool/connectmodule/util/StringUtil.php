<?php
namespace Bluecom\Paygent\lib\jp\co\ks\merchanttool\connectmodule\util;

class StringUtil
{

    /** ���ʂŕϊ�����J�^�J�i������̃}�b�s���O�����i�[���Ă���}�b�v */
    var $katakanaMap = array();

    var $zenKana = array("�A", "�C", "�E", "�G", "�I", "�J", "�L", "�N", "�P", "�R",
        "�T", "�V", "�X", "�Z", /*"�\", */
        "�^", "�`", "�c", "�e", "�g", "�i", "�j",
        "�k", "�l", "�m", "�n", "�q", "�t", "�w", "�z", "�}", "�~", "��", "��",
        "��", "��", "��", "��", "��", "��", "��", "��", "��", "��", "��", "��",
        "�K", "�M", "�O", "�Q", "�S", "�U", "�W", "�Y", "�[", "�]", "�_", "�a",
        "�d", "�f", "�h", "�o", "�r", "�u", "�x", "�{", "��", "�p", "�s", "�v",
        "�y", "�|", "�@", "�B", "�D", "�F", "�H", "��", "��", "��", "�b", "�[");

    var $hanKana = array("�", "�", "�", "�", "�", "�", "�", "�", "�", "�",
        "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�",
        "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�",
        "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�",
        "��", "��", "��", "��", "��", "��", "��", "��", "��", "��",
        "��", "��", "��", "��", "��", "��", "��", "��", "��", "��",
        "��", "��", "��", "��", "��", "��", "�", "�", "�", "�", "�",
        "�", "�", "�", "�", "�");

    /**
     * �f�t�H���g�R���X�g���N�^
     */
    function __construct()
    {

        if (count($this->zenKana) == count($this->katakanaMap)) {
            return;
        }

        for ($i = 0; $i < count($this->zenKana); $i++) {
            $this->katakanaMap[$this->zenKana[$i]] = $this->hanKana[$i];
        }
    }

    /**
     * �p�����[�^�� null �܂��͋󕶎����𔻒f����
     *
     * @param str String ���肷�镶����
     * @return <code>null</code>�܂��͋󕶎��̏ꍇ�A<code>true</code>
     */
    static function isEmpty($str)
    {
        return (!isset($str) || strlen(trim($str)) <= 0);
    }

    /**
     * split(������������)
     *
     * @param str String �����Ώە�����
     * @param delim String ��؂蕶��
     * @param limit int ���ʂ�臒l
     * @return String[] ������̕����z��
     */
    static function split($str, $delim, $limit = -1)
    {

        $delimLength = strlen($delim);
        $pos = 0;
        $index = 0;
        $list = array();
        if ($delimLength != 0) {

            while (!(($index = strpos($str, $delim, $pos)) === false)) {
                $list[] = substr($str, $pos, $index - $pos);
                $pos = $index + $delimLength;
                if ($pos >= strlen($str)) break;
            }
            if ($pos == strlen($str)) {
                $list[] = "";        // the last is the delimiter.
            } else if ($pos < strlen($str)) {
                $list[] = substr($str, $pos);
            }
        } else {
            for ($i = 0; $i < strlen($str); $i++) {
                $c = $str{$i};
                $list[] = "" . $c;
            }
        }

        $rs = &$list;

        if ((0 < $limit) && ($limit < count($rs))) {
            // limit ���A�������������ꍇ�A�������� limit �ɍ��킹��
            $temp = array();

            $pos = 0;
            for ($i = 0; $i < $limit - 1; $i++) {
                $temp[] = $rs[$i];
                $pos += strlen($rs[$i]) + strlen($delim);
            }

            $temp[$limit - 1] = substr($str, $pos);
            for ($i = $limit; $i < count($rs); $i++) {
                $sb = $temp[$limit - 1];
            }

            $rs = $temp;
        }

        return $rs;
    }

    /**
     * ���l����
     *
     * @param str String ���l����Ώە�����
     * @return boolean true=���l false=���l�ȊO
     */
    static function isNumeric($str)
    {
        $rb = is_numeric($str);

        return $rb;
    }

    /**
     * ���l�A��������
     *
     * @param str String ���l����Ώە�����
     * @param len int ����Ώ� Length
     * @return boolean true=���������l false=���l�łȂ� or �����Ⴂ
     */
    static function isNumericLength($str, $len)
    {
        $rb = false;

        if (StringUtil::isNumeric($str)) {
            if (strlen($str) == $len) {
                $rb = true;
            }
        }

        return $rb;
    }

    /**
     * �S�p�J�^�J�i�����𔼊p�J�^�J�i�̊Y�������ɕϊ�����B �w�肳�ꂽ������null�̏ꍇ��null��Ԃ��B
     *
     * @param src String �ϊ����錳�̕�����
     * @return String �ϊ���̕�����
     */
    static function convertKatakanaZenToHan($src)
    {
        if ($src == null) {
            return null;
        }
        $str = mb_convert_kana($src, "kV", "SJIS");
        return $str;
    }

    /**
     * �w�肳�ꂽ��������w�肳�ꂽ�}�b�s���O���Ɋ�Â� �ϊ��������ʂ̕������Ԃ��B �w�肳�ꂽ������null�̏ꍇ��null��Ԃ��B
     *
     * @param src String �ϊ����錳�̕�����
     * @param convertMap
     *            Map �ϊ��̑ΏۂƂȂ镶���ƕϊ���̃}�b�s���O�����i�[���Ă���}�b�v
     * @return String �ϊ���̕�����
     */
    static function convert($src, $convertMap)
    {
        if ($src == null) {
            return null;
        }
        $chars = $this->toChars($src);
        foreach ($chars as $c) {
            if (array_key_exists($c, $convertMap)) {
                $result .= $convertMap[$c];
            } else {
                $result .= $c;
            }
        }

        return $result;
    }

    static function toChars($str)
    {

        $chars = array();
        for ($i = 0; $i < mb_strlen($str); $i++) {
            $out = mb_substr($str, $i, 1);
            $chars[] = $out;
            $intx = 0;
        }
        return $chars;
    }
}

// ������
$StringUtilInit = new StringUtil();
$StringUtilInit = null;
?>