<?php

namespace TypechoPlugin\Notice\libs;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

use Typecho;

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
        $prefix = $db->getPrefix();
        $type = explode('_', $db->getAdapterName());
        $type = array_pop($type);
        if ($type != 'Mysql' and $type != 'SQLite') {
            throw new Typecho\Plugin\Exception('暂不支持当前数据库版本' . $type);
        }
        $scripts = file_get_contents('usr/plugins/Notice/scripts/' . $type . '.sql');
        $scripts = str_replace('typecho_', $prefix, $scripts);
        $scripts = str_replace('%charset%', 'utf8mb4', $scripts);
        $scripts = explode(';', $scripts);
        try {
            foreach ($scripts as $script) {
                $script = trim($script);
                if ($script) {
                    $db->query($script, Typecho\Db::WRITE);
                }
            }
            return '数据表新建成功，插件启用成功!';
        } catch (Typecho\Db\Exception $e) {
            $code = $e->getCode();
            if (('Mysql' == $type && 1050 == $code) ||
                ('SQLite' == $type && ('HY000' == $code || 1 == $code))) {
                try {
                    $script = `SELECT id, coid, type, log FROM ${prefix}notice`;
                    $db->query($script, Typecho\Db::READ);
                    return '数据表已存在，插件启用成功!';
                } catch (Typecho\Db\Exception $e) {
                    throw new Typecho\Plugin\Exception('数据表已存在但格式错误，插件启用失败。错误号：' . $e->getCode());
                }
            } else {
                throw new Typecho\Plugin\Exception('数据表建立失败，插件启用失败。错误号：' . $code);
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
        $prefix = $db->getPrefix();
        $type = explode('_', $db->getAdapterName());
        $type = array_pop($type);
        if ($type != 'Mysql' and $type != 'SQLite') {
            throw new Typecho\Plugin\Exception('暂不支持当前数据库版本' . $type);
        }
        $scripts = file_get_contents('usr/plugins/Notice/scripts/un' . $type . '.sql');
        $scripts = str_replace('typecho_', $prefix, $scripts);
        $scripts = explode(';', $scripts);
        try {
            foreach ($scripts as $script) {
                $script = trim($script);
                if ($script) {
                    $db->query($script, Typecho\Db::WRITE);
                }
            }
            return '数据库删除成功!插件卸载成功！';
        } catch (Typecho\Db\Exception $e) {
            throw new Typecho\Plugin\Exception('数据表删除失败！错误号：' . $e->getCode() . '插件卸载失败！');
        }
    }

    /**
     * @param integer $coid 评论ID
     * @param string $type wechat为server酱，mail为邮件
     * @param string $log 日志
     * @throws Typecho\Db\Exception
     */
    public static function log(int $coid, string $type, string $log)
    {
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
}
