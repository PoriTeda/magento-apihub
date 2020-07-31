<?php
namespace Bluecom\Paygent\lib\jp\co\ks\merchanttool\connectmodule\util;
/**
 * CSV�f�[�^�̉�̓N���X�B<BR>
 * �P�s���̕�����f�[�^���A���ڃ��X�g�i������z��j�ɕϊ�����B<BR>
 * �͂������̒��ɁA�f�[�^�Ƃ��Ĉ͂��������g�p�������ꍇ�́A�͂�����2�ŁA
 * 1�̈͂������f�[�^�Ƃ݂Ȃ��B<BR>
 * �͂������̒��ɑ��݂���A��؂蕶���́A��؂蕶���Ƃ��Ă݂Ȃ��B<BR>
 * ��؂蕶���̒���̕������A�͂ݕ������ǂ����ň͂ݕ��������邩�ǂ����𔻒f����B<BR>
 * �f�[�^�A��؂蕶���A�͂������ȊO�̗]�v�ȕ���
 * �i��؂蕶���̑O��̃X�y�[�X�A�^�u�Ȃǂ��j�݂͂Ƃ߂Ȃ��B
 * @version $Revision: 15878 $
 * @author $Author: orimoto $
 */


/** �f�t�H���g�̍��ڋ�؂蕶�� */
define("CSVTokenizer__DEF_SEPARATOR", ',');
/** �f�t�H���g�̍��ڃf�[�^�͂ݕ��� */
define("CSVTokenizer__DEF_ITEM_ENVELOPE", '"');
/** ���ڃf�[�^�͂ݕ���(�͂ݎ��̂Ȃ�) */
define("CSVTokenizer__NO_ITEM_ENVELOPE", chr(0));

class CSVTokenizer
{

    /** ���ڋ�؂蕶�� */
    var $separator = null;
    /** ���ڃf�[�^�͂ݕ��� */
    var $itemEnvelope = null;

    /** ��͑Ώۃf�[�^ */
    var $line;
    /** ���̓ǂݏo���J�n�ʒu */
    var $currentPos;
    /** �ŏI�Ǎ��݈ʒu */
    var $maxPos;

    /**
     * �R���X�g���N�^
     * @param separator ���ڋ�؂蕶��
     * @param envelope ���ڃf�[�^�͂ݕ���
     */
    function __construct($separator = ',',
                         $envelope = '"')
    {
        $this->separator = $separator;
        $this->itemEnvelope = $envelope;
    }

    /**
     * CSV�f�[�^�����񂩂獀�ڃf�[�^�z����擾����B
     * @param value ��͑Ώە�����i1�s���̃f�[�^�j
     * @return        �f�[�^�z��
     */
    function parseCSVData($value)
    {
        if (isset($value) == false) {
            return array();
        }
        $this->line = $value;
        $this->maxPos = strlen($this->line);
        $this->currentPos = 0;

        // ���ڃf�[�^���i�[����
        $items = array();
        // �͂ݕ�������^�Ȃ��̏�Ԕ���t���O
        $existEnvelope = false;

        while ($this->currentPos <= $this->maxPos) {
            /* �f�[�^��؂�ʒu���擾���� */
            $endPos = $this->getEndPosition($this->currentPos);

            /* �P���ڕ��̃f�[�^��ǂݎ�� */
            $temp = substr($this->line, $this->currentPos, $endPos - $this->currentPos);
            $work = "";
            // ���ڃf�[�^�Ȃ��̏ꍇ
            if (strlen($temp) == 0) {
                $work = "";
            } else {
                // �͂����������邩�`�F�b�N����
                if ($this->itemEnvelope != null
                    && $temp{0} == $this->itemEnvelope
                ) {
                    $existEnvelope = true;
                }

                $isData = false;
                for ($i = 0; $i < strlen($temp);) {
                    $chrTmp = $temp{$i};
                    if ($existEnvelope == true
                        && $temp{$i} == $this->itemEnvelope
                    ) {
                        $i++;
                        if ($isData == true) {
                            if (($i < strlen($temp))
                                && ($this->itemEnvelope != null
                                    && $temp{$i}
                                    == $this->itemEnvelope)
                            ) {
                                /* �͂ݕ������Q�����Č��ꂽ�Ƃ��́A
                                 * �����f�[�^�Ƃ��Ď擾���� */
                                $work .= $temp{$i++};
                            } else {
                                $isData = !$isData;
                            }
                        } else {
                            $isData = !$isData;
                        }
                    } else {
                        $work .= $temp{$i++};
                    }
                }
            }
            /* �P���ڕ��̃f�[�^��o�^���� */
            $items[] = $work;

            /* ���̓ǎ�ʒu�̍X�V */
            $this->currentPos = $endPos + 1;
        }
        return $items;
    }

    /**
     *    �f�[�^��؂�ʒu��Ԃ��B
     * @param        start �����J�n�ʒu
     * @return        �P�f�[�^�̋�؂�ʒu��Ԃ�
     */
    function getEndPosition($start)
    {
        // ������^������O�̏�Ԕ���t���O
        $state = false;
        // �͂ݕ�������^�Ȃ��̏�Ԕ���t���O
        $existEnvelope = false;
        // �ǂݍ��񂾕���
        $ch = null;
        // ��؂�ʒu
        $end = 0;

        if ($start >= $this->maxPos) {
            return $start;
        }

        // �͂ݕ����̗L������
        if ($this->itemEnvelope != null
            && $this->line{$start} == $this->itemEnvelope
        ) {
            $existEnvelope = true;
        }

        $end = $start;

        while ($end < $this->maxPos) {
            // �P�����ǂݍ���
            $ch = $this->line{$end};
            // �����̔���
            if ($state == false
                && $this->separator != null
                && $ch == $this->separator
            ) {
                // �����񒆂̋�؂蕶���łȂ���΁A�f�[�^��؂�
                break;
            } else if (
                $existEnvelope == true && $ch == $this->itemEnvelope
            ) {
                // �͂ݕ��������ꂽ��A������^������O�̏�Ԕ���𔽓]
                if ($state) {
                    $state = false;
                } else {
                    $state = true;
                }
            }
            // �����ʒu�̃J�E���g�A�b�v
            $end++;
        }
        return $end;
    }

    /**
     * �����񒆂ɃJ���}�����݂���ꍇ��""�ň͂ށB
     * @param str �ϊ��Ώە�����
     * @return �ϊ����ʕ�����
     */
    function cnvKnmString($str)
    {
        if (isset($str) == false) {
            return null;
        }
        for ($i = 0; $i < strlen($str); ++$i) {
            if ($str{$i} == CSVTokenizer__DEF_SEPARATOR) {

                return "\"" . $str . "\"";
            }
        }

        return $str;
    }
}

?>
