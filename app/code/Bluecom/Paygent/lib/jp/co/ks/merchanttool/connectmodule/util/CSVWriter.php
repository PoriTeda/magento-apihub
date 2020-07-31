<?php
namespace Bluecom\Paygent\lib\jp\co\ks\merchanttool\connectmodule\util;

    /**
     * CSVWriter CSV�`���Ńt�@�C�����o�͂���B
     * �g�p���@�F<br />
     * <pre><code>
     * // writer�I�u�W�F�N�g�����B�f�t�H���g��Shift_JIS�G���R�[�h
     * CSVWriter writer = null;
     * try {
     *     writer = new CSVWriter(
     *         "c:\\temp\\test.txt", CSVWriter.ENCODING_SJIS);
     *     writer.open();
     *     List list = new ArrayList();
     *     list.add("1");
     *     list.add("abc");
     *     list.add("");
     *     list.add(",");
     *     list.add("�ɂق�");
     *     writer.writeOneLine(list);
     *     list.remove(0);
     *     list.add(0, "2");
     *     writer.writeOneLine(list);
     * } finally {
     *     writer.close();
     * }
     * </code></pre>
     * @version $Revision: 15878 $
     * @author $Author: orimoto $
     */

/** �t�@�C���o�͗pEncoding Shift_JIS */
define("CSVWriter__ENCODING_SJIS", "Shift_JIS");
/** �t�@�C���o�͗pEncoding EUC-JP */
define("CSVWriter__ENCODING_EUC", "EUC_JP");
/** �t�@�C���o�͗pEncoding MS932 */
define("CSVWriter__ENCODING_MS932", "SJIS-win");	//"Windows-31J";
/** �t�@�C���o�͎��̉��s�R�[�h \r\n */
define("CSVWriter__WINDOWS_NEWLINE", "\r\n");
/** �t�@�C���o�͎��̉��s�R�[�h \n */
define("CSVWriter__UNIX_NEWLINE", "\n");
/** �t�@�C���o�͎��̉��s�R�[�h \r */
define("CSVWriter__MAC_NEWLINE", "\r");

class CSVWriter {

    var $csvFile;
    var $filePath;
    var $encoding;
    var $envelop;
    var $newLine = CSVWriter__WINDOWS_NEWLINE;

    /**
     * �R���X�g���N�^�B�G���R�[�h�y�э��ڃf�[�^�͂ݕ����̎w����s��Writer���쐬����B
     * @param filePath �t�@�C���p�X
     * @param encoding �t�@�C���̃G���R�[�h
     * @param envelop ���ڃf�[�^�͂ݕ���
     */
    function __construct($filePath, $encoding = CSVWriter__ENCODING_MS932, $envelop = '') {
        $this->filePath = $filePath;
        $this->encoding = $encoding;
        $this->envelop = '';
    }

    /**
     * �o�̓t�@�C�����J���B
     * �t�@�C���o�͂��\�ȏ�Ԃɂ���B
     * @return boolean TRUE:�����AFALSE�F���s
     */
    function open() {

        $this->csvFile = fopen($this->filePath, "w");
        if ($this->csvFile == false) {
            $this->csvFile = null;
            trigger_error("cannot open file " . $this->filePath . " to write", E_USER_NOTICE);
            return false;
        }

        // �`�F�b�N�G���R�[�f�B���O
        if (mb_convert_encoding("�G���R�[�h", $this->encoding) === false){
            trigger_error("Unsupported Encoding " . $this->encoding . ".", E_USER_NOTICE);
            return false;
        }
        return true;
    }

    /**
     * �o�̓t�@�C�������B
     * �ēx�t�@�C�����쐬����ꍇ��Open����s�����ƁB
     */
    function close() {
        if ($this->csvFile != null) {
            fclose($this->csvFile);
            $this->csvFile = null;
        }
    }

    /**
     * ���s�R�[�h��ݒ肷��B���ݒ�̏ꍇ�A\n�ŏo�͂���B
     * @param newLine ���s�R�[�h�̕�����
     */
    function setNewLine($newLine) {
        $this->newLine = $newLine;
    }

    /**
     * �t�@�C������s���������ށB�����ɉ��s�R�[�h��ǉ�����B
     * List�̏ꍇ�AList�̒��g��CSV�`���̈�s�ɕϊ����A�o�͂��s���B
     * @param line ��s���̕�����(String)�����͔z��(array)
     * @return �������߂���true�B
     */
    function writeOneLine($line) {

        if (is_string($line)) {
            if ($this->csvFile == null) {
                trigger_error("File not open.", E_USER_NOTICE);
                return false;
            }
            $encLine = $line;

            if (fwrite($this->csvFile, $line) === false) {
                trigger_error("File can not write.", E_USER_NOTICE);
                return false;
            }
            fwrite($this->csvFile, $this->newLine);
            flush($this->csvFile);
            return true;
        }
        else if (is_array($line)) {
            $strLine = "";

            // List to CSVString
            $bFirstLine = true;
            foreach($line as $i => $data) {
                if ($bFirstLine) {
                    $bFirstLine = false;
                } else {
                    $strLine .= ",";
                }

                if ($this->envelop != CSVTokenizer__NO_ITEM_ENVELOPE) {
                    $strLine .= $this->envelop;
                }
                $strLine .= $this->cnvKnmString($data);
                if ($this->envelop != CSVTokenizer__NO_ITEM_ENVELOPE) {
                    $strLine .= $this->envelop;
                }
            }

            return $this->writeOneLine($strLine);
        }
    }

    /**
     * �����񒆂ɃJ���}�����݂���ꍇ��""�ň͂ށB
     * �����񒆂Ƀ_�u���N�H�[�e�[�V���������݂���ꍇ�̓_�u���N�H�[�e�[�V�����ŃG�X�P�[�v���A
     * �_�u���N�H�[�e�[�V�����ň͂ށB
     * @param str �ϊ��Ώە�����
     * @return �ϊ����ʕ�����
     */
    function cnvKnmString($str) {
        if ($str == null) {
            return null;
        }
        $flg = false;
        $buf = "";
        for ($i = 0; $i < strlen($str); $i++) {
            if ($str{$i} == $this->envelop) {
                $buf .= $this->envelop;
                $flg = true;
            }
            if ($str{$i} == CSVTokenizer__DEF_SEPARATOR) {
                $flg = true;
            }
            $buf .= $str{$i};
        }
        return $buf;
    }
}

?>