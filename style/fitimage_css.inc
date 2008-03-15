<?php
// p2 - �X�^�C���ݒ�
// for ImageCache2:fitimage.php
// vim: set fileencoding=cp932 ai et ts=4 sw=0 sts=0 fdm=marker:
// mi: charset=Shift_JIS

if (strstr($_SERVER['HTTP_USER_AGENT'], 'MSIE')) {
	$button_position = 'absolute';
} else {
	$button_position = 'fixed';
}

echo <<<EOSTYLE

body {
	margin: 0px;
	padding: 0px;
}

div#pct {
}

div#pct img {
	cursor: pointer;
}

div#btn {
	display: none;
	position: {$button_position};
	top: 2px;
	left: 2px;
}

div#btn img {
	margin: 1px;
	cursor: pointer;
}

EOSTYLE;

// �X�^�C���̏㏑��
if (isset($MYSTYLE) && is_array($MYSTYLE)) {
	include_once (P2_STYLE_DIR . '/mystyle_css.php');
	$_mystylename = str_replace('_css.inc', '', basename(__FILE__));
	if (isset($MYSTYLE[$_mystylename])) {
		disp_mystyle($_mystylename);
	}
}

?>