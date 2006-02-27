<?php
/* vim: set fileencoding=cp932 ai et ts=4 sw=4 sts=4 fdm=marker: */
/* mi: charset=Shift_JIS */
/*
    p2 - 板メニューの非同期読み込み
    現状ではお気に板とRSSのセット切り替えのみ対応
*/

include_once './conf/conf.inc.php';  // 基本設定ファイル読込
require_once P2_LIBRARY_DIR . '/brdctl.class.php';
require_once P2_LIBRARY_DIR . '/showbrdmenupc.class.php';

$_login->authorize(); //ユーザ認証

$_conf['ktai'] = false;

$menu_php_self = '';

// {{{ HTTPヘッダとXML宣言

if (P2Util::isBrowserSafariGroup()) {
    header('Content-Type: application/xml; charset=UTF-8');
    $xmldec = '<' . '?xml version="1.0" encoding="UTF-8" ?' . '>' . "\n";
} else {
    header('Content-Type: text/html; charset=Shift_JIS');
    // 半角で「？＞」が入ってる文字列をコメントにするとパースエラー
    $xmldec = '<' . '?xml version="1.0" encoding="Shift_JIS" ?' . '>' . "\n";
    $xmldec = '';
}

// }}}
// {{{ 本体生成

// お気に板
if (isset($_GET['m_favita_set'])) {
    $aShowBrdMenuPc = &new ShowBrdMenuPc;
    ob_start();
    $aShowBrdMenuPc->print_favIta();
    $menuItem = ob_get_clean();
    $menuItem = preg_replace('/^\s*<div class="menu_cate">.+?<div class="itas" id="c_favita">\s*/s', '', $menuItem);
    $menuItem = preg_replace('/\s*<\/div>\s*<\/div>\s*$/s', '', $menuItem);

// RSS
} elseif (isset($_GET['m_rss_set'])) {
    ob_start();
    @include_once P2EX_LIBRARY_DIR . '/rss/menu.inc.php';
    $menuItem = ob_get_clean();
    $menuItem = preg_replace('/^\s*<div class="menu_cate">.+?<div class="itas" id="c_rss">\s*/s', '', $menuItem);
    $menuItem = preg_replace('/\s*<\/div>\s*<\/div>\s*$/s', '', $menuItem);

// スキン
} elseif (isset($_GET['m_skin_set'])) {
    $menuItem = changeSkin($_GET['m_skin_set']);

// その他
} else {
    $menuItem = 'p2 error: 必要な引数が指定されていません';
}

// }}}
// {{{ 本体出力

if (P2Util::isBrowserSafariGroup()) {
    $menuItem = mb_convert_encoding($menuItem, 'UTF-8', 'SJIS-win');
}
echo $xmldec;
echo $menuItem;
exit;

// }}}
// {{{ 関数

/**
 * スキンを切り替える
 */
function changeSkin($skin)
{
    global $_conf;

    if (!preg_match('/^\w+$/', $skin)) {
        return "p2 error: 不正なスキン ({$skin}) が指定されました。";
    }

    if ($skin == 'conf_style') {
        $newskin = 'conf/conf_user_style.php';
    } else {
        $newskin = 'skin/' . $skin . '.php';
    }

    if (file_exists($newskin)) {
        if (FileCtl::file_write_contents($_conf['expack.skin.setting_path'], $skin) !== FALSE) {
            return $skin;
        } else {
            return "p2 error: {$_conf['expack.skin.setting_path']} にスキン設定を書き込めませんでした。";
        }
    } else {
        return "p2 error: 不正なスキン ({$skin}) が指定されました。";
    }
}

// }}}

?>
