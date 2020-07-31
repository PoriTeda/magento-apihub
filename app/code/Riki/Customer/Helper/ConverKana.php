<?php

namespace Riki\Customer\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

class ConverKana extends AbstractHelper
{
    /**
     * @var int
     */
    protected $gMark;
    /**
     * @var int
     */
    protected $pMark;

    /**
     * ConverKana constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     */
    public function __construct(\Magento\Framework\App\Helper\Context $context)
    {
        $this->gMark = $this->JS_charCodeAt('ﾞ', 0);
        $this->pMark = $this->JS_charCodeAt('ﾟ', 0);
        parent::__construct($context);
    }

    /**
     * @param $properties
     * @param $values
     * @return array
     */
    protected function createKanaMap($properties,$values){
        $kanaMap = [];
        $strLenPro = mb_strlen($properties,'UTF-8');
        if($strLenPro == mb_strlen($values,'UTF-8') ){
            for($i = 0; $i < $strLenPro ; $i++){
                $property = $this->JS_charCodeAt($properties,$i);
                $value = $this->JS_charCodeAt($values,$i);
                $kanaMap[$property] = $value;
            }
        }
        return $kanaMap;
    }

    /**
     * @return array
     */
    private function getM(){
        return $this->createKanaMap(
            'アイウエオカキクケコサシスセソタチツテトナニヌネノハヒフヘホマミムメモヤユヨラリルレロワヲンァィゥェォッャュョ',
            'ｱｲｳｴｵｶｷｸｹｺｻｼｽｾｿﾀﾁﾂﾃﾄﾅﾆﾇﾈﾉﾊﾋﾌﾍﾎﾏﾐﾑﾒﾓﾔﾕﾖﾗﾘﾙﾚﾛﾜｦﾝｧｨｩｪｫｯｬｭｮ'
        );
    }
    /**
     * @return array
     */
    private function getG(){
        return $this->createKanaMap(
            'ガギグゲゴザジズゼゾダヂヅデドバビブベボ',
            'ｶｷｸｹｺｻｼｽｾｿﾀﾁﾂﾃﾄﾊﾋﾌﾍﾎ'
        );
    }

    /**
     * @return array
     */
    private function getP(){
        return $this->createKanaMap(
            'パピプペポ',
            'ﾊﾋﾌﾍﾎ'
        );
    }
    /**
     * @param $charCode
     * @return mixed|string
     */
    private function fromCharCode( $charCode ) {
        $strToConvert = '&#' . intval( $charCode ) . ';';
        return mb_convert_encoding( $strToConvert, 'UTF-8', 'HTML-ENTITIES' );
    }
    /**
     * @param $str
     * @return mixed|string
     */
    public function convertKanaToOneByte($str){
        if(!$str){
            return '';
        }
        $lenStr = mb_strlen($str);
        $g = $this->getG();
        $p = $this->getP();
        $m = $this->getM();
        for ($i=0;$i < $lenStr ; $i++) {
            $charCode = $this->JS_charCodeAt($str,$i);
            if(isset($g[$charCode]) || isset($p[$charCode])){
                if(isset($g[$charCode])){
                    $str = str_replace(mb_substr($str,$i,1),$this->fromCharCode($g[$charCode]).$this->fromCharCode($this->gMark),$str);
                }elseif (isset($p[$charCode])){
                    $str = str_replace(mb_substr($str,$i,1),$this->fromCharCode($p[$charCode]).$this->fromCharCode($this->pMark),$str);
                }
                else{
                    break;
                }
                // 文字列数が増加するため調整
                $i++;
                $lenStr = mb_strlen($str,'UTF-8');
            }elseif(is_numeric(mb_substr($str,$i,1))){
                $str = str_replace(mb_substr($str,$i,1),mb_convert_kana(mb_substr($str,$i,1), 'n'),$str);
            }elseif(preg_match('/[\'\/~`\!@ー・ヽヾヿ#\$%\^&\*\(\)_\-\+=\{\}\[\]\|;:"\<\>,\.\?\\\]/', mb_substr($str,$i,1))){
                $str = str_replace(mb_substr($str,$i,1),mb_convert_kana(mb_substr($str,$i,1), 'rnkh', 'utf-8'),$str);
            }elseif(preg_match('/[a-zA-Z]/',mb_substr($str,$i,1))){
                $str = str_replace(mb_substr($str,$i,1),mb_convert_kana(mb_substr($str,$i,1), 'rnkh', 'utf-8'),$str);
            }else{
                if(isset($m[$charCode])) {
                    $str = str_replace(mb_substr($str,$i,1),$this->fromCharCode($m[$charCode]),$str);
                }
            }
        }
        return $str;
    }

    /**
     * @param $str
     * @param $index
     * @return int
     */
    private function JS_charCodeAt($str, $index) {
        $utf16 = mb_convert_encoding($str, 'UTF-16LE', 'UTF-8');
        if(isset($utf16[$index*2]) && isset($utf16[$index*2+1])){
            return ord($utf16[$index*2]) + (ord($utf16[$index*2+1]) << 8);
        }
    }
}
