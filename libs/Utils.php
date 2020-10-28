<?php
class Notice_Utils{

    /**
     * 获取邮件正文模板
     *
     * @access public
     * @param string $template owner为博主，guest为访客
     * @return string
     * @throws Typecho_Widget_Exception
     */
    public static function getTemplate($template = 'owner')
    {
        $template .= '.html';
        $filename = Helper::options()->pluginDir() . '/Notice/template/' . $template;
        if (!file_exists($filename)) {
            throw new Typecho_Widget_Exception('模板文件' . $template . '不存在', 404);
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
    public static function replace($str, $coid)
    {
        $comment = Helper::widgetById('comments', $coid);
        $date = new Typecho_Date();
        $time = $date->format('Y-m-d H:i:s');
        $parent = $comment;
        if ($comment->parent) {
            $parent = Helper::widgetById('comments', $comment->parent);
        }
        $status = array(
            "approved" => "通过",
            "waiting" => "待审",
            "spam" => "垃圾"
        );
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
        $replace = array(
            Helper::options()->title,
            $comment->title,
            $comment->author,
            $parent->author,
            $comment->ip,
            $comment->mail,
            $comment->permalink,
            Helper::options()->siteUrl . __TYPECHO_ADMIN_DIR__ . "manage-comments.php",
            $comment->text,
            $parent->text,
            $time,
            $status[$comment->status]
        );
        return str_replace($search, $replace, $str);
    }
}