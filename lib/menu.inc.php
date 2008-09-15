<?php
/**
 * rep2 - 板メニュー
 * フレーム分割画面、左側部分 PC用
 *
 * menu.php, menu_side.php より読み込まれる
 */

require_once P2_LIB_DIR . '/brdctl.class.php';
require_once P2_LIB_DIR . '/showbrdmenupc.class.php';

$_login->authorize(); //ユーザ認証

//==============================================================
// 変数設定
//==============================================================
$me_url = P2Util::getMyUrl();
$me_dir_url = dirname($me_url);
// menu_side.php の URL。（ローカルパス指定はできないようだ）
$menu_side_url = $me_dir_url.'/menu_side.php';

$brd_menus = array();
$matome_i = 0;

if (isset($_GET['word'])) {
    $word = $_GET['word'];
} elseif (isset($_POST['word'])) {
    $word = $_POST['word'];
}
$hd = array('word' => '');
$GLOBALS['ita_mikke'] = array('num' => 0);

// 板検索
if (isset($word) && strlen($word) > 0) {
    if (substr_count($word, '.') == strlen($word)) {
        $word = null;
    } elseif (p2_set_filtering_word($word, 'and') !== null) {
        $hd['word'] = htmlspecialchars($word, ENT_QUOTES);
    } else {
        $word = null;
    }
}

//============================================================
// 特殊な前置処理
//============================================================
// お気に板の追加・削除
if (isset($_GET['setfavita'])) {
    include_once P2_LIB_DIR . '/setfavita.inc.php';
    setFavIta();
}

//================================================================
// ■メイン
//================================================================
$aShowBrdMenuPc = new ShowBrdMenuPc();

//============================================================
// ■ヘッダ
//============================================================
$reloaded_time = date('n/j G:i:s'); // 更新時刻
$ptitle = 'p2 - menu';

P2Util::header_nocache();
echo $_conf['doctype'];
echo <<<EOP
<html lang="ja">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=Shift_JIS">
    <meta http-equiv="Content-Style-Type" content="text/css">
    <meta http-equiv="Content-Script-Type" content="text/javascript">
    <meta name="ROBOTS" content="NOINDEX, NOFOLLOW">
    {$_conf['extra_headers_ht']}\n
EOP;

// 自動更新
if ($_conf['menu_refresh_time']) {
    $refresh_time_s = $_conf['menu_refresh_time'] * 60;
    echo <<<EOP
    <meta http-equiv="refresh" content="{$refresh_time_s};URL={$me_url}?new=1">\n
EOP;
}

echo <<<EOP
    <title>{$ptitle}</title>
    <base target="subject">
    <link rel="stylesheet" type="text/css" href="css.php?css=style&amp;skin={$skin_en}">
    <link rel="stylesheet" type="text/css" href="css.php?css=menu&amp;skin={$skin_en}">
    <link rel="shortcut icon" type="image/x-icon" href="favicon.ico">
    <script type="text/javascript" src="js/basic.js?{$_conf['p2_version_id']}"></script>
    <script type="text/javascript" src="js/showhide.js?{$_conf['p2_version_id']}"></script>
    <script type="text/javascript" src="js/menu.js?{$_conf['p2_version_id']}"></script>
    <script type="text/javascript" src="js/tgrepctl.js?{$_conf['p2_version_id']}"></script>
    <script type="text/javascript">
    //<![CDATA[
    function addSidebar(title, url) {
       if ((typeof window.sidebar == "object") && (typeof window.sidebar.addPanel == "function")) {
          window.sidebar.addPanel(title, url, '');
       } else {
          goNetscape();
       }
    }
    function goNetscape()
    {
    //  var rv = window.confirm ("This page is enhanced for use with Netscape 7.  " + "Would you like to upgrade now?");
       var rv = window.confirm ("このページは Netscape 7 用に拡張されています.  " + "今すぐアップデートしますか?");
       if (rv)
          document.location.href = "http://home.netscape.com/ja/download/download_n6.html";
    }

    function chUnColor(idnum){
        unid='un'+idnum;
        document.getElementById(unid).style.color="{$STYLE['menu_color']}";
    }

    function chMenuColor(idnum){
        newthreid='newthre'+idnum;
        if(document.getElementById(newthreid)){document.getElementById(newthreid).style.color="{$STYLE['menu_color']}";}
        unid='un'+idnum;
        document.getElementById(unid).style.color="{$STYLE['menu_color']}";
    }

    //]]>
    </script>
</head>
<body>\n
EOP;

echo $_info_msg_ht;
$_info_msg_ht = '';

if (!empty($sidebar)) {
    echo <<<EOP
<p><a href="index.php?sidebar=true" target="_content">p2 - 2ペイン表示</a></p>\n
EOP;
}

if ($_conf['enable_menu_new']) {
    echo <<<EOP
$reloaded_time [<a href="{$_SERVER['SCRIPT_NAME']}?new=1" target="_self">更新</a>]
EOP;
}

//==============================================================
// ■クイック検索
//==============================================================

    echo <<<EOP
<div id="c_search">\n
EOP;

if ($_conf['expack.google.enabled'] && $_conf['expack.google.key'] && file_exists($_conf['expack.google.wsdl'])) {
    $google_search_enabled = TRUE;
} else {
    $google_search_enabled = FALSE;
}


if ($_conf['input_type_search']) {
// {{{ <input type="search">を使う

    // 板検索
    echo <<<EOP
    <form method="GET" action="{$_SERVER['SCRIPT_NAME']}" accept-charset="{$_conf['accept_charset']}" target="_self" class="inline-form">
        <input type="search" name="word" value="{$hd['word']}" size="16" autosave="rep2.expack.search.menu" results="10" placeholder="板検索">
        {$_conf['detect_hint_input_ht']}{$_conf['k_input_ht']}
    </form><br />\n
EOP;
    // スレタイ検索
    echo <<<EOP
    <form method="GET" action="tgrepc.php" accept-charset="{$_conf['accept_charset']}" target="subject" class="inline-form">
        <input type="search" name="Q" value="" size="16" autosave="rep2.expack.search.thread" results="{$_conf['expack.tgrep.recent2_num']}" placeholder="スレタイ検索">
        {$_conf['detect_hint_input_ht']}{$_conf['k_input_ht']}
    </form><br>\n
EOP;
    // Google検索
    if ($google_search_enabled) {
        echo <<<EOP
    <form method="GET" action="gsearch.php" accept-charset="{$_conf['accept_charset']}" target="subject" class="inline-form">
        <input type="search" name="q" value="" size="16" autosave="rep2.expack.search.google" results="{$_conf['expack.google.recent2_num']}" placeholder="Google検索">
        {$_conf['detect_hint_input_ht']}{$_conf['k_input_ht']}
    </form><br>\n
EOP;
    }

// }}}
} else {
// {{{ 通常の検索フォーム

    // 板検索
    echo <<<EOP
    <form method="GET" action="{$_SERVER['SCRIPT_NAME']}" accept-charset="{$_conf['accept_charset']}" target="_self" class="inline-form" style="white-space:nowrap">
        <input type="text" name="word" value="{$hd['word']}" size="12"><input type="submit" name="submit" value="板">
        {$_conf['detect_hint_input_ht']}{$_conf['k_input_ht']}
    </form><br>\n
EOP;
    // スレタイ検索
    echo <<<EOP
    <form method="GET" action="tgrepc.php" accept-charset="{$_conf['accept_charset']}" target="subject" class="inline-form" style="white-space:nowrap">
        <input type="text" name="Q" value="" size="12"><input type="submit" value="ｽﾚ">
        {$_conf['detect_hint_input_ht']}{$_conf['k_input_ht']}
    </form><br>\n
EOP;
    // Google検索
    if ($google_search_enabled) {
        echo <<<EOP
    <form method="GET" action="gsearch.php" accept-charset="{$_conf['accept_charset']}" target="subject" class="inline-form" style="white-space:nowrap">
        <input type="text" name="q" value="" size="12"><input type="submit" value="G">
        {$_conf['detect_hint_input_ht']}{$_conf['k_input_ht']}
    </form><br>\n
EOP;
    }

// }}}
}

echo <<<EOP
</div>\n
EOP;

//==============================================================
// ■お気に板をプリントする
//==============================================================
$aShowBrdMenuPc->printFavIta();

flush();

//==============================================================
// ■tGrep一発検索をプリントする
//==============================================================
if ($_conf['expack.tgrep.quicksearch']) {
    include_once P2EX_LIB_DIR . '/tgrep/menu_quick.inc.php';
}

//==============================================================
// ■tGrep検索履歴をプリントする
//==============================================================
if ($_conf['expack.tgrep.recent_num'] > 0) {
    include_once P2EX_LIB_DIR . '/tgrep/menu_recent.inc.php';
}

//==============================================================
// ■RSSをプリントする
//==============================================================
if ($_conf['expack.rss.enabled']) {
    include_once P2EX_LIB_DIR . '/rss/menu.inc.php';
}

flush();

//==============================================================
// ■特別
//==============================================================
$norefresh_q = '&amp;norefresh=true';

echo <<<EOP
<div class="menu_cate"><b><a class="menu_cate" href="javascript:void(0);" onclick="showHide('c_spacial');" target="_self">特別</a></b>
EOP;
if ($_conf['expack.misc.multi_favs']) {
    $favlist_onchange = "openFavList('{$_conf['subject_php']}', this.options[this.selectedIndex].value, window.top.subject);";
    echo "<br>\n";
    echo FavSetManager::makeFavSetSwitchElem('m_favlist_set', 'お気にスレ', FALSE, $favlist_onchange);
}
echo <<<EOP
    <div class="itas" id="c_spacial">
EOP;

// ■新着数を表示する場合
if ($_conf['enable_menu_new'] == 1 and $_GET['new']) {
    $_has_pecl_http = ($_conf['expack.use_pecl_http'] && extension_loaded('http'));

    if ($_has_pecl_http) {
        require_once P2_LIB_DIR . '/p2httpext.class.php';
        $GLOBALS['expack.subject.multi-threaded-download.done'] = true;
    }

    if ($_has_pecl_http) {
        P2HttpRequestPool::fetchSubjectTxt($_conf['favlist_file']);
    }
    initMenuNewSp("fav");    // 新着数を初期化
    echo <<<EOP
    　<a href="{$_conf['subject_php']}?spmode=fav{$norefresh_q}" onclick="chMenuColor({$matome_i});" accesskey="f">お気にスレ</a> (<a href="{$_conf['read_new_php']}?spmode=fav" target="read" id="un{$matome_i}" onclick="chUnColor({$matome_i});"{$class_newres_num}>{$shinchaku_num}</a>)<br>
EOP;
    flush();

    if ($_has_pecl_http) {
        P2HttpRequestPool::fetchSubjectTxt($_conf['rct_file']);
    }
    initMenuNewSp("recent");    // 新着数を初期化
    echo <<<EOP
    　<a href="{$_conf['subject_php']}?spmode=recent{$norefresh_q}" onclick="chMenuColor({$matome_i});" accesskey="h">最近読んだスレ</a> (<a href="{$_conf['read_new_php']}?spmode=recent" target="read" id="un{$matome_i}" onclick="chUnColor({$matome_i});"{$class_newres_num}>{$shinchaku_num}</a>)<br>
EOP;
    flush();

    if ($_has_pecl_http) {
        P2HttpRequestPool::fetchSubjectTxt($_conf['pref_dir'] . '/p2_res_hist.idx');
    }
    initMenuNewSp("res_hist");    // 新着数を初期化
    echo <<<EOP
    　<a href="{$_conf['subject_php']}?spmode=res_hist{$norefresh_q}" onclick="chMenuColor({$matome_i});">書込履歴</a> <a href="read_res_hist.php" target="read">ログ</a> (<a href="{$_conf['read_new_php']}?spmode=res_hist" target="read" id="un{$matome_i}" onclick="chUnColor({$matome_i});"{$class_newres_num}>{$shinchaku_num}</a>)<br>
EOP;
    flush();

// 新着数を表示しない場合
} else {
    echo <<<EOP
    　<a href="{$_conf['subject_php']}?spmode=fav{$norefresh_q}" accesskey="f">お気にスレ</a><br>
    　<a href="{$_conf['subject_php']}?spmode=recent{$norefresh_q}" accesskey="h">最近読んだスレ</a><br>
    　<a href="{$_conf['subject_php']}?spmode=res_hist{$norefresh_q}">書込履歴</a> (<a href="./read_res_hist.php" target="read">ログ</a>)<br>
EOP;
}

echo <<<EOP
    　<a href="{$_conf['subject_php']}?spmode=palace{$norefresh_q}" title="DATしたスレ用のお気に入り">スレの殿堂</a><br>
    　<a href="setting.php">ログイン管理</a><br>
    　<a href="editpref.php">設定管理</a><br>
    　<a href="import.php" onclick="return OpenSubWin('import.php', 600, 380, 0, 0);">datのインポート</a><br>
    　<a href="http://find.2ch.net/" target="_blank" title="2ch公式検索">find.2ch.net</a>
    </div>
</div>\n
EOP;

//==============================================================
// ■ImageCache2
//==============================================================
if ($_conf['expack.ic2.enabled']) {
    echo <<<EOP
<div class="menu_cate"><b class="menu_cate" onclick="showHide('c_ic2');">ImageCache2</b><br>
    <div class="itas" id="c_ic2">
    　<a href="iv2.php" target="_blank">画像キャッシュ一覧</a><br>
    　<a href="ic2_setter.php">アップローダ</a>
        (<a href="#" onclick="return OpenSubWin('ic2_setter.php?popup=1', 480, 320, 1, 1);">p</a>)<br>
    　<a href="ic2_getter.php">ダウンローダ</a>
        (<a href="#" onclick="return OpenSubWin('ic2_getter.php?popup=1', 480, 320, 1, 1);">p</a>)<br>
    　<a href="ic2_manager.php">データベース管理</a>
    </div>
</div>
EOP;
}

//==============================================================
// ■カテゴリと板を表示
//==============================================================
// brd読み込み
$brd_menus_dir = BrdCtl::read_brd_dir();
$brd_menus_online = BrdCtl::read_brd_online();
$brd_menus = array_merge($brd_menus_dir, $brd_menus_online);

//===========================================================
// ■プリント
//===========================================================

// {{{ 検索ワードがあれば

if (isset($word) && strlen($word) > 0) {

    $msg_ht .= '<p>';
    if (empty($GLOBALS['ita_mikke']['num'])) {
        if (empty($GLOBALS['threti_match_ita_num'])) {
            $msg_ht .=  "\"{$hd['word']}\"を含む板は見つかりませんでした。\n";
        }
    } else {
        $match_cates = array();
        $match_cates[0] = new BrdMenuCate("&quot;{$hd['word']}&quot;を含む板 {$GLOBALS['ita_mikke']['num']}hit!\n");
        $match_cates[0]->is_open = true;
        foreach ($brd_menus as $a_brd_menu) {
            if (!empty($a_brd_menu->matches)) {
                foreach ($a_brd_menu->matches as $match_ita) {
                    $match_cates[0]->addBrdMenuIta(clone $match_ita);
                }
            }
        }
        ob_start();
        $aShowBrdMenuPc->printBrdMenu($match_cates);
        $msg_ht .= ob_get_clean();

        // 検索結果が一つなら、自動で板一覧を開く
        if ($GLOBALS['ita_mikke']['num'] == 1) {
        $msg_ht .= '（自動オープンするよ）';
            echo <<<EOP
<script type="text/javascript">
//<![CDATA[
    parent.subject.location.href="{$_conf['subject_php']}?host={$GLOBALS['ita_mikke']['host']}&bbs={$GLOBALS['ita_mikke']['bbs']}&itaj_en={$GLOBALS['ita_mikke']['itaj_en']}";
//]]>
</script>
EOP;
        }
    }
    $msg_ht .= '</p>';

    $_info_msg_ht .= $msg_ht;
} else {
    $match_cates = null;
}

// }}}

echo $_info_msg_ht;
$_info_msg_ht = "";

if ($_conf['menu_hide_brds'] && !$GLOBALS['ita_mikke']['num']) {
    $brd_menus_style = ' style="display:none"';
} else {
    $brd_menus_style = '';
}
// boardディレクトリから読み込んだユーザ定義板カテゴリメニューを表示
if ($brd_menus_dir) {
    $brd_menus_title = ($brd_menus_online) ? '板一覧 (private)' : '板一覧';
    echo <<<EOP
<hr>
<div class="menu_cate"><b class="menu_cate" onclick="showHide('c_private_boards');">【{$brd_menus_title}】</b><br>
    <div id="c_private_boards"{$brd_menus_style}>\n
EOP;
    foreach ($brd_menus_dir as $a_brd_menu) {
        $aShowBrdMenuPc->printBrdMenu($a_brd_menu->categories);
    }
    echo <<<EOP
    </div>
</div>\n
EOP;
}
// オンライン板カテゴリメニューを表示
if ($brd_menus_online) {
    $brd_menus_title = ($brd_menus_dir) ? '板一覧 (online)' : '板一覧';
    echo <<<EOP
<hr>
<div class="menu_cate"><b class="menu_cate" onclick="showHide('c_online_boards');">【{$brd_menus_title}】</b><br>
    <div id="c_online_boards"{$brd_menus_style}>\n
EOP;
    foreach ($brd_menus_online as $a_brd_menu) {
        $aShowBrdMenuPc->printBrdMenu($a_brd_menu->categories);
    }
    echo <<<EOP
    </div>
</div>\n
EOP;
}

//==============================================================
// フッタを表示
//==============================================================

// ■for Mozilla Sidebar
if (empty($sidebar)) {
    echo <<<EOP
<script type="text/javascript">
//<![CDATA[
if ((typeof window.sidebar == "object") && (typeof window.sidebar.addPanel == "function")) {
    document.writeln("<p><a href=\"javascript:void(0);\" onclick=\"addSidebar('p2 Menu', '{$menu_side_url}');\">p2 Menuを Sidebar に追加<" + "/a><" + "/p>");
}
//]]>
</script>\n
EOP;
}

echo '</body></html>';


//==============================================================
// 関数
//==============================================================
/**
 * spmode用のmenuの新着数を初期化する
 */
function initMenuNewSp($spmode_in)
{
    global $shinchaku_num, $matome_i, $host, $bbs, $spmode, $STYLE, $class_newres_num;
    $matome_i++;
    $host = "";
    $bbs = "";
    $spmode = $spmode_in;
    include P2_LIB_DIR . '/subject_new.inc.php'; // $shinchaku_num, $_newthre_num をセット
    if ($shinchaku_num > 0) {
        $class_newres_num = ' class="newres_num"';
    } else {
        $class_newres_num = ' class="newres_num_zero"';
    }
}

/*
 * Local Variables:
 * mode: php
 * coding: cp932
 * tab-width: 4
 * c-basic-offset: 4
 * indent-tabs-mode: nil
 * End:
 */
// vim: set syn=php fenc=cp932 ai et ts=4 sw=4 sts=4 fdm=marker:
