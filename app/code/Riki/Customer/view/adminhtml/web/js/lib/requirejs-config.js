/* required js config */
var config = {
    paths: {
        'ajaxzip3_amb': 'Riki_Customer/js/lib/ajaxzip3-source'
    },
    'shim': {
        'ajaxzip3_amb': {
            deps: ['jquery', 'mage/translate'],
            exports: 'AjaxZip3',
            init: function ($, $t) {
                $.extend(
                    this.AjaxZip3, {
                        JSONDATA: '//yubinbango.github.io/yubinbango-data/data',
                        PREFMAP_ENG: [
                            null,           'Hokkaido',     'Aomori',       'Iwate',    'Miyagi',
                            ' Akita',       'Yamagata',     'Fukushima',    'Ibaraki',  'Tochigi',
                            ' Gunma',       'Saitama',      'Chiba',        'Tokyo',    'Kanagawa' ,
                            ' Niigata',     'Toyama',       'Ishikawa',     'Fukui',    'Yamanashi',
                            ' Nagano',      'Gifu',         'Shizuoka',     'Aichi',    'Mie' ,
                            ' Shiga',       'Kyoto',        'Osaka',        'Hyogo',    'Nara',
                            ' Wakayama',    'Tottori',      'Shimane',      'Okayama',  'Hiroshima',
                            ' Yamaguchi',   'Tokushima',    'Kagawa',       'Ehime',    'Kochi',
                            ' Fukuoka',     'Saga',         'Nagasaki',     'Kumamoto', 'Oita',
                            ' Miyazaki',    'Kagoshima',    'Okinawa'
                        ],
                        elementMap: [],
                        setElementMap: function (elementMap) {
                            AjaxZip3.elementMap = elementMap;
                        },
                        updateValue: function (element, value) {
                            if (AjaxZip3.elementMap[element.id]) {
                                AjaxZip3.elementMap[element.id].value(value);
                            } else {
                                element.value = value;
                            }
                        },
                        onFailure: function(){
                            alert('登録の郵便番号は存在しません。\n正しい郵便番号であるかご確認ください。');
                        },
                        zipjsonpquery: function () {
                            var url = AjaxZip3.JSONDATA+'/'+AjaxZip3.nzip.substr(0,3)+'.js';
                            var scriptTag = document.createElement("script");
                            scriptTag.setAttribute("type", "text/javascript");
                            scriptTag.setAttribute("charset", "UTF-8");
                            scriptTag.setAttribute("src", url);

                            scriptTag.onerror = AjaxZip3.onFailure;

                            document.getElementsByTagName("head").item(0).appendChild(scriptTag);
                        },
                        callback: function(data){
                            function onFailure( ){
                                if(typeof AjaxZip3.onFailure === 'function' ) AjaxZip3.onFailure();
                            }
                            var array = data[AjaxZip3.nzip];
                            // Opera バグ対策：0x00800000 を超える添字は +0xff000000 されてしまう
                            var opera = (AjaxZip3.nzip-0+0xff000000)+"";
                            if (! array && data[opera] ) array = data[opera];
                            if (! array ) {
                                onFailure();
                                return;
                            }
                            var pref_id = array[0];                 // 都道府県ID
                            if (! pref_id ) {
                                onFailure();
                                return;
                            }
                            var jpref = AjaxZip3.PREFMAP[pref_id];  // 都道府県名
                            var epref = AjaxZip3.PREFMAP_ENG[pref_id];
                            if (! jpref ) {
                                onFailure();
                                return;
                            }

                            var jcity = array[1];
                            if (! jcity ) jcity = '';              // 市区町村名
                            var jarea = array[2];
                            if (! jarea ) jarea = '';              // 町域名
                            var jstrt = array[3];
                            if (! jstrt ) jstrt = '';              // 番地

                            var cursor = AjaxZip3.faddr;
                            var jaddr = jcity;                      // 市区町村名

                            if (AjaxZip3.fpref.type == 'select-one' || AjaxZip3.fpref.type == 'select-multiple' ) {
                                var opts = AjaxZip3.fpref.options,
                                    hasKoElement = typeof AjaxZip3.elementMap[AjaxZip3.fpref.id] != 'undefined',
                                    selected = false;

                                // 都道府県プルダウンの場合
                                for( var i=0; i<opts.length; i++ ) {
                                    var vpref = opts[i].value;
                                    var tpref = opts[i].text;

                                    selected = ( vpref == pref_id || vpref == jpref || tpref == jpref || tpref == epref );

                                    if (hasKoElement && selected) {
                                        this.updateValue(AjaxZip3.fpref, opts[i].value);
                                    } else if (selected) {
                                        opts[i].selected = 'selected';
                                    }
                                }
                            } else {
                                if (AjaxZip3.fpref.name == AjaxZip3.faddr.name ) {
                                    // 都道府県名＋市区町村名＋町域名合体の場合
                                    jaddr = jpref + jaddr;
                                } else {
                                    // 都道府県名テキスト入力の場合
                                    AjaxZip3.updateValue(AjaxZip3.fpref, jpref);
                                }
                            }
                            if (AjaxZip3.farea ) {
                                cursor = AjaxZip3.farea;
                                AjaxZip3.updateValue(AjaxZip3.farea, jarea);
                            } else {
                                jaddr += jarea;
                            }
                            if (AjaxZip3.fstrt ) {
                                cursor = AjaxZip3.fstrt;
                                if (AjaxZip3.faddr.name == AjaxZip3.fstrt.name ) {
                                    // 市区町村名＋町域名＋番地合体の場合
                                    jaddr = jaddr + jstrt;
                                } else if (jstrt ) {
                                    // 番地テキスト入力欄がある場合
                                    AjaxZip3.updateValue(AjaxZip3.fstrt, jstrt);
                                }
                            }

                            AjaxZip3.updateValue(AjaxZip3.faddr, jaddr);

                            if(typeof AjaxZip3.onSuccess === 'function' ) AjaxZip3.onSuccess();

                            // patch from http://iwa-ya.sakura.ne.jp/blog/2006/10/20/050037
                            // update http://www.kawa.net/works/ajax/AjaxZip2/AjaxZip2.html#com-2006-12-15T04:41:22Z
                            if (!AjaxZip3.ffocus ) return;
                            if (! cursor ) return;
                            if (! cursor.value ) return;
                            var len = cursor.value.length;
                            cursor.focus();
                            if (cursor.createTextRange ) {
                                var range = cursor.createTextRange();
                                range.move('character', len);
                                range.select();
                            } else if (cursor.setSelectionRange) {
                                cursor.setSelectionRange(len,len);
                            }

                        }
                    }
                );

                return this.AjaxZip3;
            }
        }
    }
};