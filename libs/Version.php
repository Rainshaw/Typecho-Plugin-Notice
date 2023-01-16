<?php
namespace TypechoPlugin\Notice\libs;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

use Typecho;
use Utils;

class Version
{


    /**
     * 从缓存中获取最新版本
     *
     * @access public
     * @return string
     */
    public static function getNewRelease(): string
    {
        $date = new Typecho\Date();
        $date = $date->timeStamp;
        $filename = Utils\Helper::options()->pluginDir() . '/Notice/cache/version.json';
        $data = file_get_contents($filename);
        if ($data) {
            $data = json_decode($data, true);
            if ($date - $data['time'] < 86400) {
                return $data['version'];
            }
        }
        $tag = Version::getNewReleaseFromGithub();
        $data = json_encode(array(
            "version" => $tag,
            "time" => $date
        ));
        file_put_contents($filename, $data);
        return $tag;
    }


    /**
     * 获取 Github 最新 Release Tag 版本
     *
     * @access public
     * @return string
     */
    public static function getNewReleaseFromGithub(): string
    {
        $ch = curl_init("https://api.github.com/repos/Rainshaw/Typecho-Plugin-Notice/releases/latest");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, "Typecho-Plugin-Notice");
        $res = curl_exec($ch);
        $data = json_decode($res, JSON_UNESCAPED_UNICODE);
        return $data['tag_name'];
    }
}
