<?php
/*
	p2 - �܂�BBS�p�̊֐��i�� JBBS@������΁j
*/

require_once './filectl.class.php';
require_once './p2util.class.php';	// p2�p�̃��[�e�B���e�B�N���X

/**
 * �܂�BBS�� read.pl ��ǂ�� dat�ɕۑ�����
 */
function machiDownload()
{
	global $aThread, $machi_latest_num;

	$machi_latest_num = "";
	
	// ����dat�̎擾���X�����K�����ǂ�����O�̂��߃`�F�b�N
	if (file_exists($aThread->keydat)) {
		$dls = @file($aThread->keydat);
		if (sizeof($dls) != $aThread->gotnum) {
			// echo 'bad size!<br>';
			unlink($aThread->keydat);
			$aThread->gotnum = 0;
		}
	}
	
	$aThread->gotnum = intval($aThread->gotnum);
	
	if ($aThread->gotnum == 0) {
		$mode = 'wb';
		$START = 1;
	} else {
		$mode = 'ab';
		$START = $aThread->gotnum + 1;
	}

	// JBBS@�������
	if (P2Util::isHostJbbsShitaraba($aThread->host)) {
		// ������΂�livedoor�ړ]�ɑΉ��B�Ǎ����livedoor�Ƃ���B
		$host = P2Util::adjustHostJbbs($aThread->host);
		list($host, $category, ) = explode('/', $host);
		$machiurl = "http://{$host}/bbs/read.cgi/{$category}/{$aThread->bbs}/{$aThread->key}/{$START}-";
		
	// �܂�BBS
	} else { 
		$machiurl = "http://{$aThread->host}/bbs/read.pl?BBS={$aThread->bbs}&KEY={$aThread->key}&START={$START}";
	}

	$tempfile = $aThread->keydat.'.html.temp';
	
	FileCtl::mkdir_for($tempfile);	
	$machiurl_res = P2Util::fileDownload($machiurl, $tempfile);
	
	if ($machiurl_res->is_error()) {
		$aThread->diedat = true;
		return false;
	}
	
	// ������΂Ȃ�EUC��SJIS�ɕϊ�
	if (P2Util::isHostJbbsShitaraba($aThread->host)) { 
	
		$temp_data = @file_get_contents($tempfile);
		
		$temp_data = mb_convert_encoding($temp_data, 'SJIS-win', 'EUC-JP');
		$fp = @fopen($tempfile, 'wb') or die("Error: $tempfile ���X�V�ł��܂���ł���");
		@flock($fp, LOCK_EX);
		fputs($fp, $temp_data);
		@flock($fp, LOCK_UN);
		fclose($fp);
	}
	
	$mlines = @file($tempfile);
	if (file_exists($tempfile)) {
		unlink($tempfile);
	}

	// �i�܂�BBS�j<html>error</html>
	if (trim($mlines[0]) == "<html>error</html>") {
		$aThread->getdat_error_msg_ht .= "error";
		$aThread->diedat = true;
		return false;
	// �iJBBS�jERROR!: �X���b�h������܂���B�ߋ����O�q�ɂɂ�����܂���B
	} elseif (preg_match("/^ERROR.*$/i", $mlines[0], $matches)) {
		$aThread->getdat_error_msg_ht .= $matches[0];
		$aThread->diedat = true;
		return false;
	}
	
	$mdatlines = machiHtmltoDatLines($mlines);

	// �������� =====================================
	$fp = @fopen($aThread->keydat, $mode) or die("Error: $aThread->keydat ���X�V�ł��܂���ł���");
	@flock($fp, LOCK_EX);
	for ($i = $START; $i <= $machi_latest_num; $i++) {
		if ($mdatlines[$i]) {
			fputs($fp, $mdatlines[$i]);
		} else {
			fputs($fp, "���ځ[��<>���ځ[��<>���ځ[��<>���ځ[��<>\n");
		}
	}
	@flock($fp, LOCK_UN);
	fclose($fp);
	
	$aThread->isonline = true;
	return true;
}


/**
 * �܂�BBS��read.pl�œǂݍ���HTML��dat�ɕϊ�����
 */
function machiHtmltoDatLines($mlines)
{
	global $machi_latest_num;

	if (!$mlines) {return false;}
	$mdatlines = "";
	
	foreach ($mlines as $ml) {
		$ml = rtrim($ml);
		if (!$tuduku) {
			unset($order, $mail, $name, $date, $ip, $body);
		}

		if ($tuduku) {
			if (preg_match("/^ \]<\/font><br><dd>(.*) <br><br>$/i", $ml, $matches)) {
				$body = $matches[1];
			} else {
				unset($tuduku);
				continue;
			}
		} elseif (preg_match("/^<dt>(?:<a[^>]+?>)?(\d+)(?:<\/a>)? ���O�F(<font color=\"#.+?\">|<a href=\"mailto:(.*)\">)<b> (.+) <\/b>(<\/font>|<\/a>) ���e���F (.+)<br><dd>(.*) <br><br>$/i", $ml, $matches)) {
			$order = $matches[1];
			$mail = $matches[3];
			$name = preg_replace("/<font color=\"?#.+?\"?>(.+)<\/font>/i", "\\1", $matches[4]);
			$date = $matches[6];
			$body = $matches[7];
		} elseif (preg_match('{<title>(.*)</title>}i', $ml, $matches)) {
			$mtitle = $matches[1];
			continue;
		} elseif (preg_match("/^<dt>(?:<a[^>]+?>)?(\d+)(?:<\/a>)? ���O�F(<font color=\"#.+?\">|<a href=\"mailto:(.*)\">)<b> (.+) <\/b>(<\/font>|<\/a>) ���e���F (.+) <font size=1>\[ (.+)$/i", $ml, $matches)) {
			$order = $matches[1];
			$mail = $matches[3];
			$name = preg_replace('{<font color="?#.+?"?>(.+)</font>}i', '$1', $matches[4]);
			$date = $matches[6];
			$ip = $matches[7];
			$tuduku = true;
			continue;
		}
		
		if ($ip) {
			$date = "$date [$ip]";
		}

		// �������JBBS jbbs.livedoor.com ��link.cgi������
		// <a href="http://jbbs.livedoor.jp/bbs/link.cgi?url=http://dempa.2ch.net/gazo/free/img-box/img20030424164949.gif" target="_blank">http://dempa.2ch.net/gazo/free/img-box/img20030424164949.gif</a>
		$body = preg_replace('{<a href="(?:http://jbbs\.(?:shitaraba\.com|livedoor\.(?:com|jp)))?/bbs/link\.cgi\?url=([^"]+)" target="_blank">([^><]+)</a>}i', '$1', $body);
		
		// �����N�O��
		$body = preg_replace('{<a href="(https?://[-_.!~*\'()a-zA-Z0-9;/?:@&=+\$,%#]+)" target="_blank">(https?://[-_.!~*\'()a-zA-Z0-9;/?:@&=+\$,%#]+)</a>}i', '$1', $body);
		
		if ($order == 1) {
			$datline = $name.'<>'.$mail.'<>'.$date.'<>'.$body.'<>'.$mtitle."\n";
		} else {
			$datline = $name.'<>'.$mail.'<>'.$date.'<>'.$body.'<>'."\n";
		}
		$mdatlines[$order] = $datline;
		if ($order > $machi_latest_num) {
			$machi_latest_num = $order;
		}
		unset($tuduku);
	}
	
	return $mdatlines;
}

?>