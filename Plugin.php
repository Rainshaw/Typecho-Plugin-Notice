<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
require_once 'phpmailer/PHPMailer.php';
require_once 'phpmailer/SMTP.php';
require_once 'phpmailer/Exception.php';

/**
 * <strong style="color:#28B7FF;font-family: 楷体;">评论通知</strong>
 *
 * @package Notice
 * @author <strong style="color:#28B7FF;font-family: 楷体;">Rainshaw</strong>
 * @version 0.2.2
 * @link https://github.com/RainshawGao
 * @dependence 18.10.23
 */
class Notice_Plugin implements Typecho_Plugin_Interface
{
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
        Typecho_Plugin::factory('Widget_Feedback')->finishComment = array(__CLASS__, 'requestService');
        Typecho_Plugin::factory('Widget_Comments_Edit')->finishComment = array(__CLASS__, 'requestService');
        Typecho_Plugin::factory('Widget_Comments_Edit')->mark = array(__CLASS__, 'approvedMail');
        Typecho_Plugin::factory('Widget_Service')->sendSC = array(__CLASS__, 'sendSC');
        Typecho_Plugin::factory('Widget_Service')->sendQmsg = array(__CLASS__, 'sendQmsg');
        Typecho_Plugin::factory('Widget_Service')->sendMail = array(__CLASS__, 'sendMail');
        Typecho_Plugin::factory('Widget_Service')->sendApprovedMail = array(__CLASS__, 'sendApprovedMail');

        return _t($s . '请设置Servechan密钥和邮箱信息，以便插件正常使用。');
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
        try {
            $delDB = Helper::options()->plugin('Notice')->delDB;
        } catch (Typecho_Plugin_Exception $e) {
            return '禁用出现错误:' . $e . '为避免数据损失，不删除数据库！';
        }
        if ($delDB == 1) {
            return self::dbUninstall();
        } else {
            return _t('注意！您的设置为不删除数据库！');
        }
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
        $serviceTitle = new Typecho_Widget_Helper_Layout('div', array('class=' => 'typecho-page-title'));
        $serviceTitle->html('<h1>推送服务配置</h1>');
        $form->addItem($serviceTitle);

        $setting = new Typecho_Widget_Helper_Form_Element_Checkbox('setting',
            array(
                'serverchan' => '启用Server酱',
                'qmsg' => '启用Qmsg酱',
                'mail' => '启用邮件',
            ),
            NULL, '启用设置', _t('请选择您要启用的通知方式。'));
        $form->addInput($setting->multiMode());

        $delDB = new Typecho_Widget_Helper_Form_Element_Radio('delDB',
            array(
                '1' => '是',
                '0' => '否'
            ), '0', _t('卸载插件时删除数据库'),
            _t('取消勾选则表示当您禁用此插件时，插件的历史记录仍将存留在数据库中。'));
        $form->addInput($delDB);

        // Server 酱
        $serviceTitle = new Typecho_Widget_Helper_Layout('div', array('class=' => 'typecho-page-title'));
        $serviceTitle->html('<h2><a href="http://sc.ftqq.com/">Server酱</a>配置</h2>');
        $form->addItem($serviceTitle);

        $scKey = new Typecho_Widget_Helper_Form_Element_Text('scKey', NULL, NULL, _t('Server酱SCKEY'),
            _t('想要获取 SCKEY 则需要在 <a href="https://sc.ftqq.com/">Server酱</a> 使用 Github 账户登录<br>
                同时，注册后需要在 <a href="http://sc.ftqq.com/">Server酱</a> 绑定你的微信号才能收到推送'));
        $form->addInput($scKey);

        $scMsg = new Typecho_Widget_Helper_Form_Element_TextArea('scMsg', NULL,
            "评论人：**{author}**\n\n 评论内容:\n> {text}\n\n链接：{permalink}",
            _t("Server酱通知模版"), _t("通过server酱通知您的内容模版，可使用变量列表见插件说明")
        );
        $form->addInput($scMsg);

        // Qmsg 酱
        $serviceTitle = new Typecho_Widget_Helper_Layout('div', array('class=' => 'typecho-page-title'));
        $serviceTitle->html('<h2><a href="https://qmsg.zendee.cn/">Qmsg酱</a>配置</h2>');
        $form->addItem($serviceTitle);

        $QmsgKey = new Typecho_Widget_Helper_Form_Element_Text('QmsgKey', NULL, NULL, _t('QmsgKey'),
            _t('请进入 <a href="https://qmsg.zendee.cn/api">Qmsg酱文档</a> 获取您的 KEY: https://qmsg.zendee.cn:443/send/{QmsgKey}'));
        $form->addInput($QmsgKey);

        $QmsgQQ = new Typecho_Widget_Helper_Form_Element_Text('QmsgQQ', NULL, NULL, _t('QmsgQQ'),
            _t('请进入 <a href="https://qmsg.zendee.cn/me">Qmsg酱</a> 选择机器人QQ号，使用您接收通知的QQ号添加其为好友，并将该QQ号添加到该页面下方QQ号列表中<br/>
                如果您有多个应用，且在该网站上增加了许多QQ号，您可以在这里填写本站点推送的QQ号（用英文逗号分割，最后不需要加逗号），不填则向该网站列表中所有的QQ号发送消息'));
        $form->addInput($QmsgQQ);

        $QmsgMsg = new Typecho_Widget_Helper_Form_Element_TextArea('QmsgMsg', NULL,
            "评论人：{author}\n评论内容:\n{text}\n\n链接：{permalink}",
            _t("Qmsg酱通知模版"), _t("通过Qmsg酱通知您的内容模版，可使用变量列表见插件说明")
        );
        $form->addInput($QmsgMsg);

        // SMTP
        $serviceTitle = new Typecho_Widget_Helper_Layout('div', array('class=' => 'typecho-page-title'));
        $serviceTitle->html('<h2>SMTP配置</h2>');
        $form->addItem($serviceTitle);
        $host = new Typecho_Widget_Helper_Form_Element_Text('host', NULL, '',
            _t('邮件服务器地址'), _t('请填写 SMTP 服务器地址'));
        $form->addInput($host);

        $port = new Typecho_Widget_Helper_Form_Element_Text('port', null, 465,
            _t('端口号'), _t('端口号必须是数字，一般为465'));
        $form->addInput($port->addRule('isInteger', _t('端口号必须是数字')));

        $ssl = new Typecho_Widget_Helper_Form_Element_Select('secure',
            array('tls' => 'tls', 'ssl' => 'ssl'), 'ssl',
            _t('连接加密方式'));
        $form->addInput($ssl);

        $auth = new Typecho_Widget_Helper_Form_Element_Radio('auth',
            array(1 => '是', 0 => '否'), 1,
            _t('启用身份验证'), _t('勾选后必须填写用户名和密码两项'));
        $form->addInput($auth);

        $user = new Typecho_Widget_Helper_Form_Element_Text('user', NULL,
            '', _t('用户名'), _t('启用身份验证后有效，一般为 name@domain.com '));
        $form->addInput($user);

        $pwd = new Typecho_Widget_Helper_Form_Element_Text('password', NULL,
            '', _t('密码'), _t('启用身份验证后有效，有些服务商可能需要专用密码，详询服务商客服'));
        $form->addInput($pwd);

        $from = new Typecho_Widget_Helper_Form_Element_Text('from', NULL,
            '', _t('发信人邮箱'));
        $form->addInput($from->addRule('email', _t('请输入正确的邮箱地址')));

        $from_name = new Typecho_Widget_Helper_Form_Element_Text('from_name', NULL,
            Helper::options()->title, _t('发信人名称'), _t('默认为站点标题'));
        $form->addInput($from_name);


        $titleForOwner = new Typecho_Widget_Helper_Form_Element_Text('titleForOwner', null,
            "[{title}] 一文有新的评论", _t('博主接收邮件标题'));
        $form->addInput($titleForOwner->addRule('required', _t('博主接收邮件标题 不能为空')));

        $titleForGuest = new Typecho_Widget_Helper_Form_Element_Text('titleForGuest', null,
            "您在 [{title}] 的评论有了回复", _t('访客接收邮件标题'));
        $form->addInput($titleForGuest->addRule('required', _t('访客接收邮件标题 不能为空')));

        $titleForApproved = new Typecho_Widget_Helper_Form_Element_Text('titleForApproved', null,
            "您在 [{title}] 的评论已被审核通过", _t('访客接收邮件标题'));
        $form->addInput($titleForApproved->addRule('required', _t('访客接收邮件标题 不能为空')));

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

        if (in_array('serverchan', $settings['setting'])) {
            if (empty($settings['scKey'])) {
                return _t('请填写SCKEY');
            }
            if (empty($settings['scMsg'])) {
                return _t('请填写Server酱通知模版');
            }
        }
        if (in_array('qmsg', $settings['setting'])) {
            if (empty($settings['QmsgKey'])) {
                return _t('请填写QmsgKEY');
            }
            if (empty($settings['QmsgMsg'])) {
                return _t('请填写Qmsg酱通知模版');
            }
        }
        if (in_array('mail', $settings['setting'])) {
            if (empty($settings['host'])) {
                return _t('请填写SMTP服务器地址');
            }
            if (empty($settings['port'])) {
                return _t('请填写端口号');
            }
            if ($settings['auth'] == 1) {
                if (empty($settings['user'])) {
                    return _t('请填写SMTP用户名');
                }
                if (empty($settings['password'])) {
                    return _t('请填写SMTP密码');
                }
            }
            if (empty($settings['from'])) {
                return _t('请填写发信人邮箱');
            }

            if (empty($settings['titleForOwner'])) {
                return _t('请填写博主接收邮件标题');
            }
            if (empty($settings['titleForGuest'])) {
                return _t('请填写访客接收邮件标题');
            }
        }
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
     * 审核通过评论回掉
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

        self::log($coid, 'qq', $result ."\n\n" . $msg);
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
            return '建立邮件队列数据表，插件启用成功!';
        } catch (Typecho_Db_Exception $e) {
            $code = $e->getCode();
            if (('Mysql' == $type && 1050 == $code) ||
                ('SQLite' == $type && ('HY000' == $code || 1 == $code))) {
                try {
                    $script = 'SELECT `id`, `coid`, `type`, `log` FROM `' . $prefix . 'notice`';
                    $db->query($script, Typecho_Db::READ);
                    return '检测到邮件队列数据表，插件启用成功!';
                } catch (Typecho_Db_Exception $e) {
                    throw new Typecho_Plugin_Exception('数据表检测失败，插件启用失败。错误号：' . $e->getCode());
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
            return '数据库删除成功!';
        } catch (Typecho_Db_Exception $e) {
            throw new Typecho_Plugin_Exception('数据表删除失败！错误号：' . $e->getCode());
        }
    }

}