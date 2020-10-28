<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
require_once 'libs/phpmailer/PHPMailer.php';
require_once 'libs/phpmailer/SMTP.php';
require_once 'libs/phpmailer/Exception.php';
require_once 'libs/Config.php';

define('__TYPECHO_PLUGIN_NOTICE_VERSION__', '0.5.0');

/**
 * <strong style="color:#28B7FF;font-family: 楷体;">评论通知</strong>
 *
 * @package Notice
 * @author <strong style="color:#28B7FF;font-family: 楷体;">Rainshaw</strong>
 * @version 0.5.0
 * @link https://github.com/RainshawGao
 * @dependence 18.10.23
 */
class Notice_Plugin implements Typecho_Plugin_Interface
{
    /** @var string 插件配置action前缀 */
    public static $action_setting='Plugin-Notice-Setting';

    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     *
     * @access public
     * @return string
     * @throws Typecho_Plugin_Exception | Typecho_Db_Exception
     */
    public static function activate()
    {
        $s = self::dbInstall();
        // 更新提示
        Typecho_Plugin::factory('admin/menu.php')->navBar = array(__CLASS__, 'updateTip');
        // 通知触发函数
        Typecho_Plugin::factory('Widget_Feedback')->finishComment = array(__CLASS__, 'requestService');
        Typecho_Plugin::factory('Widget_Comments_Edit')->finishComment = array(__CLASS__, 'requestService');
        Typecho_Plugin::factory('Widget_Comments_Edit')->mark = array(__CLASS__, 'approvedMail');
        // 注册异步调用函数
        Typecho_Plugin::factory('Widget_Service')->sendSC = array(__CLASS__, 'sendSC');
        Typecho_Plugin::factory('Widget_Service')->sendQmsg = array(__CLASS__, 'sendQmsg');
        Typecho_Plugin::factory('Widget_Service')->sendMail = array(__CLASS__, 'sendMail');
        Typecho_Plugin::factory('Widget_Service')->sendApprovedMail = array(__CLASS__, 'sendApprovedMail');

        Helper::addAction(self::$action_setting, 'Notice_libs_SettingAction');

        return '<div id="AS-SW" style="border-radius:2px;box-shadow:1px 1px 50px rgba(0,0,0,.3);background-color: #fff;width: auto; height: auto; z-index: 2501554; position: fixed; margin-left: -125px; margin-top: -75px; left: 50%; top: 50%;">
                    <div style="text-align: center;height:42px;line-height:42px;border-bottom:1px solid #eee;font-size:14px;overflow:hidden;border-radius:2px 2px 0 0;font-weight:bold;position:relative;cursor:move;min-width:200px;box-sizing:border-box;background-color:#28B7FF;color:#fff;">
                        ' . $s . '
                    </div>
                    <div style="padding:15px;font-size:14px;min-width:150px;position:relative;box-sizing:border-box;height: 50px;">
                        欢迎使用Notice插件，希望能让您喜欢！
                    </div>
                    <div style="text-align:right;padding-bottom:15px;padding-right:10px;min-width:200px;box-sizing:border-box;">
                        <button onclick="colseDIV()"style="height:28px;line-height:28px;margin:15px 5px 0;padding:0 15px;border-radius:2px;font-weight:400;cursor:pointer;text-decoration:none;outline:none;background-color:#28B7FF;border:0;color:#fff;">
                            关闭
                        </button>
                    </div>
                    <Script>function colseDIV(){$("#AS-SW").hide()}</Script>
                </div>';
    }

    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     *
     * @static
     * @access public
     * @return string
     * @throws Typecho_Db_Exception
     * @throws Typecho_Exception
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate()
    {
        Helper::removeAction('Notice-setting');
        $delDB = Helper::options()->plugin('Notice')->delDB;
        if ($delDB == 1) {
            $s = self::dbUninstall();
        } else {
            $s = _t('您的设置为不删除数据库！插件卸载成功！');
        }
        return '<div id="AS-SW" style="border-radius:2px;box-shadow:1px 1px 50px rgba(0,0,0,.3);background-color: #fff;width: auto; height: auto; z-index: 2501554; position: fixed; margin-left: -125px; margin-top: -75px; left: 50%; top: 50%;">
                    <div style="text-align: center;height:42px;line-height:42px;border-bottom:1px solid #eee;font-size:14px;overflow:hidden;border-radius:2px 2px 0 0;font-weight:bold;position:relative;cursor:move;min-width:200px;box-sizing:border-box;background-color:#28B7FF;color:#fff;">
                        ' . $s . '
                    </div>
                    <div style="padding:15px;font-size:14px;min-width:150px;position:relative;box-sizing:border-box;height: 50px;">
                        感谢您使用Notice，期待与您的下一次相遇！
                    </div>
                    <div style="text-align:right;padding-bottom:15px;padding-right:10px;min-width:200px;box-sizing:border-box;">
                        <button onclick="colseDIV()" style="height:28px;line-height:28px;margin:15px 5px 0;padding:0 15px;border-radius:2px;font-weight:400;cursor:pointer;text-decoration:none;outline:none;background-color:#28B7FF;border:0;color:#fff;">
                            关闭
                        </button>
                    </div>
                    <Script>function colseDIV(){$("#AS-SW").hide()}</Script>
                </div>';
    }

    /**
     * 获取插件配置面板
     *
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form)
    {
        // CSS
        Notice_Config::style($form);
        // header
        Notice_Config::header($form);
        // 配置开始
        $form->addItem(new MDCustomLabel('<div class="mdui-panel" mdui-panel="">'));
        {
            // 插件配置
            Notice_Config::Setting($form);

            // Server 酱
            Notice_Config::Serverchan($form);

            // Qmsg 酱
            Notice_Config::Qmsgchan($form);

            // SMTP
            Notice_Config::SMTP($form);
        }
        $form->addItem(new Typecho_Widget_Helper_Layout('/div'));
        // 美化提交按钮
        $submit = new Typecho_Widget_Helper_Form_Element_Submit(NULL, NULL, _t('保存设置'));
        $submit->input->setAttribute('class', 'mdui-btn mdui-color-theme-accent mdui-ripple submit_only');
        $form->addItem($submit);
        // javascript
        Notice_Config::script($form);

    }

    /**
     * 个人用户的配置面板
     *
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form)
    {
    }

    /**
     * 检查参数
     *
     * @param array $settings
     * @return string
     */
    public static function configCheck(array $settings)
    {
        return Notice_Config::check($settings);
    }

    /**
     * 评论通知回调
     *
     * @access public
     * @param $comment
     * @return void
     * @throws Typecho_Plugin_Exception
     * @throws Typecho_Db_Exception
     */
    public static function requestService($comment)
    {
        $options = Helper::options()->plugin('Notice');
        if (in_array('mail', $options->setting) && !empty($options->host)) {
            Helper::requestService('sendMail', $comment->coid);
        }
        if (in_array('serverchan', $options->setting) && !empty($options->scKey)) {
            Helper::requestService('sendSC', $comment->coid);
        }
        if (in_array('qmsg', $options->setting) && !empty($options->QmsgKey)) {
            Helper::requestService('sendQmsg', $comment->coid);
        }
    }

    /**
     * 审核通过评论回调
     *
     * @access public
     * @param $comment
     * @param $edit
     * @param string $status
     * @return void
     * @throws Typecho_Db_Exception
     */
    public static function approvedMail($comment, $edit, $status)
    {
        if ('approved' === $status) {
            self::log($comment['coid'], 0, 0);
            Helper::requestService('sendApprovedMail', $comment['coid']);
            self::log($comment['coid'], 1, 1);
        }
    }

    /**
     * 异步发送微信 Powered By Server酱
     *
     * @param integer $coid 评论ID
     * @return void
     * @throws Typecho_Db_Exception
     * @throws Typecho_Plugin_Exception
     * @access public
     */
    public static function sendSC($coid)
    {
        $options = Helper::options();
        $pluginOptions = $options->plugin('Notice');
        $comment = Helper::widgetById('comments', $coid);
        if (empty($pluginOptions->scKey)) {
            return;
        }
        $key = $pluginOptions->scKey;
        if (!$comment->have() || empty($comment->mail)) {
            return;
        }
        if ($comment->authorId == 1) {
            return;
        }

        $msg = $pluginOptions->scMsg;
        $msg = self::replace($msg, $coid);

        $postdata = http_build_query(
            array(
                'text' => "有人在您的博客发表了评论",
                'desp' => $msg
            )
        );

        $opts = array('http' =>
            array(
                'method' => 'POST',
                'header' => 'Content-type: application/x-www-form-urlencoded',
                'content' => $postdata
            )
        );
        $context = stream_context_create($opts);
        $result = file_get_contents('https://sc.ftqq.com/' . $key . '.send', false, $context);

        self::log($coid, 'wechat', $result . "\n\n" . $msg);
    }

    /**
     * 异步发送微信 Powered By Server酱
     *
     * @param integer $coid 评论ID
     * @return void
     * @throws Typecho_Db_Exception
     * @throws Typecho_Plugin_Exception
     * @access public
     */
    public static function sendQmsg($coid)
    {
        $options = Helper::options();
        $pluginOptions = $options->plugin('Notice');
        $comment = Helper::widgetById('comments', $coid);
        if (empty($pluginOptions->QmsgKey)) {
            return;
        }
        $key = $pluginOptions->QmsgKey;
        if (!$comment->have() || empty($comment->mail)) {
            return;
        }
        if ($comment->authorId == 1) {
            return;
        }

        $msg = $pluginOptions->QmsgMsg;
        $msg = self::replace($msg, $coid);

        if ($pluginOptions->QmsgQQ == NULL) {
            $postdata = http_build_query(
                array(
                    'msg' => $msg
                )
            );
        } else {
            $postdata = http_build_query(
                array(
                    'msg' => $msg,
                    'qq' => $pluginOptions->QmsgQQ
                )
            );
        }

        $opts = array('http' =>
            array(
                'method' => 'POST',
                'header' => 'Content-type: application/x-www-form-urlencoded',
                'content' => $postdata
            )
        );
        $context = stream_context_create($opts);
        $result = file_get_contents('https://qmsg.zendee.cn/send/' . $key, false, $context);

        self::log($coid, 'qq', $result . "\n\n" . $msg);
    }

    /**
     * 获取邮件正文模板
     *
     * @access private
     * @param string $template owner为博主，guest为访客
     * @return string
     * @throws Typecho_Widget_Exception
     */
    private static function getTemplate($template = 'owner')
    {
        $template .= '.html';
        $filename = Helper::options()->pluginDir() . '/Notice/template/' . $template;
        if (!file_exists($filename)) {
            throw new Typecho_Widget_Exception('模板文件' . $template . '不存在', 404);
        }

        return file_get_contents($filename);
    }

    /**
     * 异步发送通知邮件
     *
     * @access public
     * @param int $coid 评论id
     * @return void
     * @throws Typecho_Plugin_Exception
     * @throws Typecho_Widget_Exception
     * @throws \PHPMailer\PHPMailer\Exception
     */
    public static function sendMail($coid)
    {
        $options = Helper::options();
        $pluginOptions = $options->plugin('Notice');
        $comment = Helper::widgetById('comments', $coid);

        if (!in_array('mail', $pluginOptions->setting)) {
            return;
        }

        if (empty($pluginOptions->host)) {
            return;
        }

        if (!$comment->have() || empty($comment->mail)) {
            return;
        }

        $mail = new PHPMailer\PHPMailer\PHPMailer(false);

        $mail->isSMTP();
        $mail->Host = $pluginOptions->host;
        $mail->SMTPAuth = !!$pluginOptions->auth;
        $mail->Username = $pluginOptions->user;
        $mail->Password = $pluginOptions->password;
        $mail->SMTPSecure = $pluginOptions->secure;
        $mail->Port = $pluginOptions->port;
        $mail->getSMTPInstance()->setTimeout(10);
        $mail->isHTML(true);
        $mail->CharSet = 'utf-8';
        $mail->setFrom($pluginOptions->from, $pluginOptions->from_name);


        if (0 == $comment->parent) {
            // 某文章或页面的新评论，向博主发信
            if ($comment->ownerId != $comment->authorId) {
                // 如果评论者不是文章作者自身，则发信
                $post = Helper::widgetById('contents', $comment->cid);
                $mail->addAddress($post->author->mail, $post->author->name);
                // 构造邮件
                $mail->Subject = self::replace($pluginOptions->titleForOwner, $coid);
                $mail->Body = self::replace(self::getTemplate('owner'), $coid);
                $mail->AltBody = "作者：" .
                    $comment->author . "\r\n链接：" .
                    $comment->permalink .
                    "\r\n评论：\r\n" .
                    $comment->text;
                $mail->send();
            }
        } else {
            // 某评论有新的子评论，向父评论发信
            if ('approved' == $comment->status) {
                // 如果评论者之前有通过审核的评论，该评论会直接通过审核，则向父评论发信
                $parent = Helper::widgetById('comments', $comment->parent);
                $mail->addAddress($parent->mail, $parent->author);
                // 构造邮件
                $mail->Subject = self::replace($pluginOptions->titleForGuest, $coid);
                $mail->Body = self::replace(self::getTemplate('guest'), $coid);
                $mail->AltBody = "作者：" .
                    $comment->author .
                    "\r\n链接：" .
                    $comment->permalink .
                    "\r\n评论：\r\n" .
                    $comment->text;
                $mail->send();
            }
        }
    }

    /**
     * 异步发送评论通过审核邮件
     *
     * @access public
     * @param int $coid 评论id
     * @return void
     * @throws Typecho_Plugin_Exception
     * @throws Typecho_Widget_Exception
     * @throws \PHPMailer\PHPMailer\Exception
     */
    public static function sendApprovedMail($coid)
    {
        $options = Helper::options();
        $pluginOptions = $options->plugin('Notice');
        $comment = Helper::widgetById('comments', $coid);

        if (!in_array('mail', $pluginOptions->setting)) {
            return;
        }

        if (empty($pluginOptions->host)) {
            return;
        }

        if (!$comment->have() || empty($comment->mail)) {
            return;
        }

        $mail = new PHPMailer\PHPMailer\PHPMailer(false);

        $mail->isSMTP();
        $mail->Host = $pluginOptions->host;
        $mail->SMTPAuth = !!$pluginOptions->auth;
        $mail->Username = $pluginOptions->user;
        $mail->Password = $pluginOptions->password;
        $mail->SMTPSecure = $pluginOptions->secure;
        $mail->Port = $pluginOptions->port;
        $mail->getSMTPInstance()->setTimeout(10);
        $mail->isHTML(true);
        $mail->CharSet = 'utf-8';
        $mail->setFrom($pluginOptions->from, $pluginOptions->from_name);

        $mail->addAddress($comment->mail, $comment->author);
        $mail->Subject = self::replace($pluginOptions->titleForApproved, $coid);
        $mail->Body = self::replace(self::getTemplate('approved'), $coid);
        $mail->AltBody = "您的评论已通过审核。\n";
        $mail->send();
    }

    /**
     * 替换内容
     *
     * @access private
     * @param string $str 模版
     * @param integer $coid 评论ID
     * @return string
     */
    private static function replace($str, $coid)
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

    /**
     * 更新提示
     *
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function updateTip()
    {
        $option = Helper::options()->plugin('Notice');
        if (in_array('updatetip', $option->setting)) {
            $date = new Typecho_Date();
            $date = $date->timeStamp;
            $data = file_get_contents(__DIR__ . '/cache/version.json');
            if ($data) {
                $data = json_decode($data, true);
                if ($date - $data['time'] < 86400) {
                    if ($data['version'] > __TYPECHO_PLUGIN_NOTICE_VERSION__) {
                        echo '<a href="https://github.com/RainshawGao/Typecho-Plugin-Notice/releases">Notice插件有更新</a>';
                        return;
                    } else {
                        return;
                    }
                }
            }
            //
            $tag = self::getNewRelease();
            $data = json_encode(array(
                "version" => $tag,
                "time" => $date
            ));
            file_put_contents(__DIR__ . '/cache/version.json', $data);
            if ($tag > __TYPECHO_PLUGIN_NOTICE_VERSION__) {
                echo '<a href="https://github.com/RainshawGao/Typecho-Plugin-Notice/releases">Notice插件有更新</a>';
                return;
            } else {
                return;
            }

        }
    }

    /**
     * 获取 Github 最新 Release Tag 版本
     *
     * @access private
     * @return string
     */
    private static function getNewRelease()
    {
        $ch = curl_init("https://api.github.com/repos/RainshawGao/Typecho-Plugin-Notice/releases/latest");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, "Typecho-Plugin-Notice");
        $res = curl_exec($ch);
        $data = json_decode($res, JSON_UNESCAPED_UNICODE);
        return $data['tag_name'];
    }

    /**
     * 数据库初始化
     *
     * @access private
     * @return string
     * @throws Typecho_Plugin_Exception
     * @throws Typecho_Db_Exception
     */
    private static function dbInstall()
    {
        $db = Typecho_Db::get();
        $prefix = $db->getPrefix();
        $type = explode('_', $db->getAdapterName());
        $type = array_pop($type);
        if ($type != 'Mysql' and $type != 'SQLite') {
            throw new Typecho_Plugin_Exception('暂不支持当前数据库版本' . $type);
        }
        $scripts = file_get_contents('usr/plugins/Notice/scripts/' . $type . '.sql');
        $scripts = str_replace('typecho_', $prefix, $scripts);
        $scripts = str_replace('%charset%', 'utf8mb4', $scripts);
        $scripts = explode(';', $scripts);
        try {
            foreach ($scripts as $script) {
                $script = trim($script);
                if ($script) {
                    $db->query($script, Typecho_Db::WRITE);
                }
            }
            return '数据表新建成功，插件启用成功!';
        } catch (Typecho_Db_Exception $e) {
            $code = $e->getCode();
            if (('Mysql' == $type && 1050 == $code) ||
                ('SQLite' == $type && ('HY000' == $code || 1 == $code))) {
                try {
                    $script = 'SELECT `id`, `coid`, `type`, `log` FROM `' . $prefix . 'notice`';
                    $db->query($script, Typecho_Db::READ);
                    return '数据表已存在，插件启用成功!';
                } catch (Typecho_Db_Exception $e) {
                    throw new Typecho_Plugin_Exception('数据表已存在但格式错误，插件启用失败。错误号：' . $e->getCode());
                }
            } else {
                throw new Typecho_Plugin_Exception('数据表建立失败，插件启用失败。错误号：' . $code);
            }
        }
    }

    /**
     * 数据库卸载
     *
     * @access private
     * @return string
     * @throws Typecho_Plugin_Exception
     * @throws Typecho_Db_Exception
     */
    private static function dbUninstall()
    {

        $db = Typecho_Db::get();
        $prefix = $db->getPrefix();
        $type = explode('_', $db->getAdapterName());
        $type = array_pop($type);
        if ($type != 'Mysql' and $type != 'SQLite') {
            throw new Typecho_Plugin_Exception('暂不支持当前数据库版本' . $type);
        }
        $scripts = file_get_contents('usr/plugins/Notice/scripts/un' . $type . '.sql');
        $scripts = str_replace('typecho_', $prefix, $scripts);
        $scripts = explode(';', $scripts);
        try {
            foreach ($scripts as $script) {
                $script = trim($script);
                if ($script) {
                    $db->query($script, Typecho_Db::WRITE);
                }
            }
            return '数据库删除成功!插件卸载成功！';
        } catch (Typecho_Db_Exception $e) {
            throw new Typecho_Plugin_Exception('数据表删除失败！错误号：' . $e->getCode() . '插件卸载失败！');
        }
    }

    /**
     * @param integer $coid 评论ID
     * @param string $type wechat为server酱，mail为邮件
     * @param string $log 日志
     * @throws Typecho_Db_Exception
     */
    private static function log($coid, $type, $log)
    {
        $db = Typecho_Db::get();
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