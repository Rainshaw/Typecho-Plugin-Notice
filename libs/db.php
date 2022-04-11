<?php

namespace TypechoPlugin\Notice\libs;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

use Typecho;
use Utils;

class DB
{
    /**
     * 数据库初始化
     *
     * @access public
     * @return string
     * @throws Typecho\Plugin\Exception
     * @throws Typecho\Db\Exception
     */
    public static function dbInstall(): string
    {
        $db = Typecho\Db::get();
        $scripts = DB::get_scripts(true, $db);
        try {
            foreach ($scripts as $script) {
                $script = trim($script);
                if ($script) {
                    $db->query($script, Typecho\Db::WRITE);
                }
            }
            return "数据表新建成功，插件启用成功！";
        } catch (Typecho\Db\Exception $e) {
            $type = DB::get_db_type($db);
            if (('Mysql' == $type && 1050 == $e->getCode()) ||
                ('SQLite' == $type && ('HY000' == $e->getCode() || 1 == $e->getCode()))) {
                try {
                    $prefix = $db->getPrefix();
                    $script = sprintf("SELECT id, coid, type, log FROM %snotice", $prefix);
                    $db->query($script, Typecho\Db::READ);
                    return "数据表已存在，插件启用成功！";
                } catch (Typecho\Db\Exception $e) {
                    throw new Typecho\Plugin\Exception("数据表已存在但格式错误，插件启用失败。\n错误号：" . $e->getCode() . "\n错误信息：" . $e->getMessage());
                }
            } else {
                throw new Typecho\Plugin\Exception("数据表建立失败，插件启用失败。\n错误号：" . $e->getCode() . "\n错误信息：" . $e->getMessage());
            }
        }
    }

    /**
     * 数据库卸载
     *
     * @access public
     * @return string
     * @throws Typecho\Plugin\Exception
     * @throws Typecho\Db\Exception
     */
    public static function dbUninstall(): string
    {
        $db = Typecho\Db::get();
        $scripts = self::get_scripts(false, $db);
        try {
            foreach ($scripts as $script) {
                $script = trim($script);
                if ($script) {
                    $db->query($script, Typecho\Db::WRITE);
                }
            }
            return '数据库删除成功！插件卸载成功！';
        } catch (Typecho\Db\Exception $e) {
            throw new Typecho\Plugin\Exception('数据表删除失败！错误号：' . $e->getCode() . '插件卸载失败！');
        }
    }

    /**
     * @param integer $coid 评论ID
     * @param string $type wechat为server酱，mail为邮件
     * @param string $log 日志
     * @throws Typecho\Db\Exception
     * @throws Typecho\Plugin\Exception
     */
    public static function log(int $coid, string $type, string $log)
    {
        $pluginOption = Utils\Helper::options()->plugin('Notice');
        switch ($pluginOption->enableLog){
            case "0":
                return;
            case "1":
                if ($type == "log"){
                    return;
                }
            case "2":
        }
        $db = Typecho\Db::get();
        $prefix = $db->getPrefix();

        $id = $db->query(
            $db->insert($prefix . 'notice')->rows(array(
                'coid' => $coid,
                'type' => $type,
                'log' => $log
            ))
        );
    }

    /**
     * @access public
     * @throws Typecho\Plugin\Exception
     */
    public static function get_db_type(Typecho\Db $db): string
    {
        $type = $db->getAdapterName();
        if (count(explode("Mysql", $type)) > 1) {
            $type = "Mysql";
        } elseif (count(explode("SQLite", $type)) > 1) {
            $type = "SQLite";
        } else {
            throw new Typecho\Plugin\Exception('暂不支持当前数据库版本' . $type);
        }
        return $type;
    }

    /**
     * @access public
     * @throws Typecho\Plugin\Exception
     */
    public static function get_scripts(bool $install, Typecho\Db $db): array
    {
        $prefix = $db->getPrefix();
        $type = DB::get_db_type($db);
        if ($install) {
            $scripts = file_get_contents('usr/plugins/Notice/scripts/' . $type . '.sql');
        } else {
            $scripts = file_get_contents('usr/plugins/Notice/scripts/un' . $type . '.sql');
        }
        $scripts = str_replace('typecho_', $prefix, $scripts);
        $scripts = str_replace('%charset%', 'utf8mb4', $scripts);
        return explode(';', $scripts);
    }
}
