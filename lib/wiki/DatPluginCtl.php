<?php
/*

save(array)                 データを保存
load()                      データを読み込んで返す(自動的に実行される)
clear()                     データを削除
*/

require_once __DIR__ . '/WikiPluginCtlBase.php';

class DatPluginCtl extends WikiPluginCtlBase
{
    protected $filename = 'p2_plugin_dat.txt';
    protected $data = array();

    public function clear()
    {
        global $_conf;

        $path = $_conf['pref_dir'] . '/' . $this->filename;

        return @unlink($path);
    }

    public function load()
    {
        global $_conf;

        $path = $_conf['pref_dir'].'/'.$this->filename;
        if ($lines = FileCtl::file_read_lines($path)) {
            foreach ($lines as $l) {
                $lar = explode("\t", trim($l));
                if (strlen($lar[0]) == 0) {
                    continue;
                }
                $ar = array(
                    'title'   => $lar[0], // 題名
                    'match'   => $lar[1], // Match
                    'replace' => $lar[2], // Replace
                );
                $this->data[] = $ar;
            }
        }

        return $this->data;
    }

    /**
     * $data[$i]['title']       題名
     * $data[$i]['match']       Match
     * $data[$i]['replace']     Replace
     * $data[$i]['del']         削除
     */
    public function save($data)
    {
        global $_conf;

        $path = $_conf['pref_dir'] . '/' . $this->filename;

        $newdata = '';

        foreach ($data as $na_info) {
            $a[0] = strtr(trim($na_info['title'], "\t\r\n"), "\t\r\n", "   ");
            $a[1] = strtr(trim($na_info['match'], "\t\r\n"), "\t\r\n", "   ");
            $a[2] = strtr(trim($na_info['replace'], "\t\r\n"), "\t\r\n", "   ");
            if ($na_info['del'] || ($a[0] === '' || $a[1] === '' || $a[2] === '')) {
                continue;
            }
            $newdata .= implode("\t", $a) . "\n";
        }

        return FileCtl::file_write_contents($path, $newdata);
    }

    public function getData()
    {
        return $this->data;
    }
}
