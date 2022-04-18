<?php
namespace TypechoPlugin\Notice\libs;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

use Typecho;
use Utils;
use Widget;


class ShortCut
{

    /**
     * 获取邮件正文模板
     *
     * @access public
     * @param string $template owner为博主，guest为访客
     * @return string
     * @throws Typecho\Widget\Exception
     */
    public static function getTemplate(string $template = 'owner'): string
    {
        $template .= '.html';
        $filename = Utils\Helper::options()->pluginDir() . '/Notice/template/' . $template;
        if (!file_exists($filename)) {
            throw new Typecho\Widget\Exception('模板文件' . $template . '不存在', 404);
        }

        return file_get_contents($filename);
    }


    /**
     * 替换内容
     *
     * @access public
     * @param string $str 模版
     * @param integer $coid 评论ID
     * @return string
     */

    public static function replace(string $str, int $coid): string
    {
        $comment = Utils\Helper::widgetById('comments', $coid);
        assert($comment instanceof Widget\Base\Comments);
        $date = new Typecho\Date();
        $time = $date->format('Y-m-d H:i:s');
        if ($comment->parent) {
            $parent = Utils\Helper::widgetById('comments', $comment->parent);
            assert($parent instanceof Widget\Base\Comments);
            # widgetById 返回逻辑会覆盖
            # https://github.com/typecho/typecho/issues/1412
            $p_author = $parent->author;
            $p_text = $parent->text;
            $comment = Utils\Helper::widgetById('comments', $coid);
            assert($parent instanceof Widget\Base\Comments);
        } else {
            $p_author = "";
            $p_text = "";
        }
        $status = array(
            "approved" => "通过",
            "waiting" => "待审",
            "spam" => "垃圾"
        );
        $replace = array(
            Utils\Helper::options()->title,
            $comment->title,
            $comment->author,
            $p_author,
            $comment->ip,
            $comment->mail,
            $comment->permalink,
            Utils\Helper::options()->siteUrl . __TYPECHO_ADMIN_DIR__ . "manage-comments.php",
            $comment->text,
            $p_text,
            $time,
            $status[$comment->status]
        );
        return self::replaceArray($str, $replace);
    }

    public static function replaceArray($str, $replace)
    {
        $search = array(
            "{siteTitle}",
            "{title}",
            "{author}",
            "{author_p}",
            "{ip}",
            "{mail}",
            "{permalink}",
            "{manage}",
            "{text}",
            "{text_p}",
            "{time}",
            "{status}"
        );
        return str_replace($search, $replace, $str);
    }
}
