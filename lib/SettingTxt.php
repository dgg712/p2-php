<?php
/**
 * SettingTxtクラス
 *
 * @since 2006/02/27
 */
class SettingTxt{

    var $host;
    var $bbs;
    var $url;
    var $setting_txt;   // SETTING.TXT
    var $setting_cache; // p2_kb_setting.srd
    var $setting_array = array();
    var $cache_interval;

    /**
     * コンストラクタ
     */
    function SettingTxt($host, $bbs)
    {
        $this->cache_interval = 60 * 60 * 12; // キャッシュは12時間有効

        $this->host = $host;
        $this->bbs =  $bbs;

        $dat_bbs_dir = P2Util::datDirOfHost($this->host) . '/' . $this->bbs;
        $this->setting_txt = $dat_bbs_dir . '/SETTING.TXT';
        $this->setting_cache = $dat_bbs_dir . '/p2_kb_setting.srd';

        $this->url = "http://" . $this->host . '/' . $this->bbs . "/SETTING.TXT";
        //$this->url = P2Util::adjustHostJbbs($this->url); // したらばのlivedoor移転に対応。読込先をlivedoorとする。

        // SETTING.TXT をダウンロード＆セットする
        $this->dlAndSetData();
    }

    /**
     * SETTING.TXT をダウンロード＆セットする
     *
     * @return boolean セットできれば true、できなければ false
     */
    function dlAndSetData()
    {
        $this->downloadSettingTxt();

        if ($this->setSettingArray()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * SETTING.TXT をダウンロードして、パースして、キャッシュする
     *
     * @return boolean 実行成否
     */
    function downloadSettingTxt()
    {
        global $_conf, $_info_msg_ht;

        $perm = (isset($_conf['dl_perm'])) ? $_conf['dl_perm'] : 0606;

        FileCtl::mkdir_for($this->setting_txt); // 板ディレクトリが無ければ作る

        if (file_exists($this->setting_cache) && file_exists($this->setting_txt)) {
            // 更新しない場合は、その場で抜けてしまう
            if (!empty($_GET['norefresh']) || isset($_REQUEST['word'])) {
                return true;
            // キャッシュが新しい場合も抜ける
            } elseif ($this->isCacheFresh()) {
                return true;
            }
            $modified = gmdate("D, d M Y H:i:s", filemtime($this->setting_txt)) . " GMT";
        } else {
            $modified = false;
        }

        // DL
        include_once "HTTP/Request.php";

        $params = array("timeout" => $_conf['fsockopen_time_limit']);
        if ($_conf['proxy_use']) {
            $params = array("proxy_host" => $_conf['proxy_host']);
            $params = array("proxy_port" => $_conf['proxy_port']);
        }
        $req =& new HTTP_Request($this->url, $params);
        $modified && $req->addHeader("If-Modified-Since", $modified);
        $req->addHeader('User-Agent', 'Monazilla/1.00 (' . $_conf['p2name'] . '/' . $_conf['p2version'] . ')');

        $response = $req->sendRequest();

        if (PEAR::isError($response)) {
            $error_msg = $response->getMessage();
        } else {
            $code = $req->getResponseCode();

            if ($code == 302) {
                // ホストの移転を追跡
                include_once P2_LIBRARY_DIR . '/BbsMap.class.php';
                $new_host = BbsMap::getCurrentHost($this->host, $this->bbs);
                if ($new_host != $this->host) {
                    $aNewSettingTxt = &new SettingTxt($new_host, $this->bbs);
                    $body = $aNewSettingTxt->downloadSettingTxt();
                    return true;
                }
            }

            if (!($code == 200 || $code == 206 || $code == 304)) {
                //var_dump($req->getResponseHeader());
                $error_msg = $code;
            }
        }

        // DLエラー
        if (isset($error_msg) && strlen($error_msg) > 0) {
            $url_t = P2Util::throughIme($this->url);
            $_info_msg_ht .= "<div>Error: {$error_msg}<br>";
            $_info_msg_ht .= "p2 info: <a href=\"{$url_t}\"{$_conf['ext_win_target_at']}>{$this->url}</a> に接続できませんでした。</div>";
            touch($this->setting_txt); // DL失敗した場合も touch
            return false;

        }

        $body = $req->getResponseBody();

        // DL成功して かつ 更新されていたら保存
        if ($body && $code != "304") {

            // したらば or be.2ch.net ならEUCをSJISに変換
            if (P2Util::isHostJbbsShitaraba($this->host) || P2Util::isHostBe2chNet($this->host)) {
                $body = mb_convert_encoding($body, 'SJIS-win', 'eucJP-win');
            }

            if (FileCtl::file_write_contents($this->setting_txt, $body) === false) {
                die("Error: cannot write file");
            }
            chmod($this->setting_txt, $perm);

            // パースしてキャッシュを保存する
            if (!$this->cacheParsedSettingTxt()) {
                return false;
            }

        } else {
            // touchすることで更新インターバルが効くので、しばらく再チェックされなくなる
            touch($this->setting_txt);
        }

        return true;
    }


    /**
     * キャッシュが新鮮なら true を返す
     *
     * @return boolean 新鮮なら true。そうでなければ false。
     */
    function isCacheFresh()
    {
        // キャッシュがある場合
        if (file_exists($this->setting_cache)) {
            // キャッシュの更新が指定時間以内なら
            // clearstatcache();
            if (filemtime($this->setting_cache) > time() - $this->cache_interval) {
                return true;
            }
        }

        return false;
    }

    /**
     * SETTING.TXT をパースしてキャッシュ保存する
     *
     * 成功すれば、$this->setting_array がセットされる
     *
     * @return boolean 実行成否
     */
    function cacheParsedSettingTxt()
    {
        global $_conf;

        $this->setting_array = array();

        if (!$lines = file($this->setting_txt)) {
            return false;
        }

        foreach ($lines as $line) {
            if (strstr($line, '=')) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                $this->setting_array[$key] = $value;
            }
        }
        $this->setting_array['p2version'] = $_conf['p2version'];

        // パースキャッシュファイルを保存する
        if (file_put_contents($this->setting_cache, serialize($this->setting_array), LOCK_EX) === false) {
            return false;
        }

        return true;
    }

    /**
     * SETTING.TXT のパースデータを読み込む
     *
     * 成功すれば、$this->setting_array がセットされる
     *
     * @return boolean 実行成否
     */
    function setSettingArray()
    {
        global $_conf;

        if (!file_exists($this->setting_cache)) {
            return false;
        }

        $this->setting_array = unserialize(file_get_contents($this->setting_cache));

        /*
        if ($this->setting_array['p2version'] != $_conf['p2version']) {
            unlink($this->setting_cache);
            unlink($this->setting_txt);
        }
        */

        if (!empty($this->setting_array)) {
            return true;
        } else {
            return false;
        }
    }

}

?>
