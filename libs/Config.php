<?php
/*表单组件*/
require("formelement/MDFormElements.php");
require('formelement/MDCheckbox.php');
require('formelement/MDText.php');
require('formelement/MDRadio.php');
require('formelement/MDSelect.php');
require('formelement/MDTextarea.php');

class Notice_Config
{
    public static function style(Typecho_Widget_Helper_Form $form)
    {
        $option = Helper::options();
        echo '<link href="https://cdn.jsdelivr.net/npm/mdui@0.4.3/dist/css/mdui.min.css" rel="stylesheet">';
        echo '<script src="https://cdn.jsdelivr.net/npm/mdui@0.4.3/dist/js/mdui.min.js"></script>';
        echo '<script src="https://cdn.jsdelivr.net/npm/jquery@2.2.4/dist/jquery.min.js" type="text/javascript"></script>';
        echo '<link href="' . $option->pluginUrl . '/Notice/assets/notice.css" rel="stylesheet" type="text/css"/>';
        echo '<script src="' . $option->pluginUrl . '/Notice/assets/notice.js"></script>';
    }

    public static function header(Typecho_Widget_Helper_Form $form)
    {

    }

    public static function Setting(Typecho_Widget_Helper_Form $form)
    {
        $form->addItem(new MDTitle('推送服务配置', '推送服务开关、插件更新提示、数据库配置', false));

        $setting = new MDCheckbox('setting',
            array(
                'serverchan' => '启用Server酱',
                'qmsg' => '启用Qmsg酱',
                'mail' => '启用邮件',
                'updatetip' => '启用更新提示',
            ),
            array('updatetip'), '插件设置', _t('请选择您要启用的通知方式。<br/>' .
                '当勾选"启用更新提示"时，在本插件更新后，您会在后台界面看到一条更新提示～'));
        $form->addInput($setting->multiMode());

        $delDB = new MDRadio('delDB',
            array(
                '1' => '是',
                '0' => '否'
            ), '0', _t('卸载插件时删除数据库'),
            _t('取消勾选则表示当您禁用此插件时，插件的历史记录仍将存留在数据库中。'));
        $form->addInput($delDB);
        $form->addItem(new Typecho_Widget_Helper_Layout('/div'));
        $form->addItem(new Typecho_Widget_Helper_Layout('/div'));
    }

    public static function Serverchan(Typecho_Widget_Helper_Form $form)
    {
        $form->addItem(new MDTitle('Server酱配置', 'SCKEY、Server酱通知模版', false));
        $scKey = new MDText('scKey', NULL, NULL, _t('Server酱SCKEY'),
            _t('想要获取 SCKEY 则需要在 <a href="https://sc.ftqq.com/">Server酱</a> 使用 Github 账户登录<br>
                同时，注册后需要在 <a href="http://sc.ftqq.com/">Server酱</a> 绑定你的微信号才能收到推送'));
        $form->addInput($scKey);

        $scMsg = new MDTextarea('scMsg', NULL,
            "评论人：**{author}**\n\n 评论内容:\n> {text}\n\n链接：{permalink}",
            _t("Server酱通知模版"), _t("通过server酱通知您的内容模版，可使用变量列表见插件说明")
        );
        $form->addInput($scMsg);
        $form->addItem(new Typecho_Widget_Helper_Layout('/div'));
        $form->addItem(new Typecho_Widget_Helper_Layout('/div'));
    }

    public static function checkServerchan(array $settings)
    {
        if (in_array('serverchan', $settings['setting'])) {
            if (empty($settings['scKey'])) {
                return _t('请填写SCKEY');
            }
            if (empty($settings['scMsg'])) {
                return _t('请填写Server酱通知模版');
            }
        }
        return '';
    }

    public static function Qmsgchan(Typecho_Widget_Helper_Form $form)
    {
        $form->addItem(new MDTitle('Qmsg酱配置', 'QmsgKEY、QmsgQQ、Qmsg酱通知模版', false));
        $QmsgKey = new MDText('QmsgKey', NULL, NULL, _t('QmsgKey'),
            _t('请进入 <a href="https://qmsg.zendee.cn/api">Qmsg酱文档</a> 获取您的 KEY: https://qmsg.zendee.cn:443/send/{QmsgKey}'));
        $form->addInput($QmsgKey);

        $QmsgQQ = new MDText('QmsgQQ', NULL, NULL, _t('QmsgQQ'),
            _t('请进入 <a href="https://qmsg.zendee.cn/me">Qmsg酱</a> 选择机器人QQ号，使用您接收通知的QQ号添加其为好友，并将该QQ号添加到该页面下方QQ号列表中<br/>
                如果您有多个应用，且在该网站上增加了许多QQ号，您可以在这里填写本站点推送的QQ号（用英文逗号分割，最后不需要加逗号），不填则向该网站列表中所有的QQ号发送消息'));
        $form->addInput($QmsgQQ);

        $QmsgMsg = new MDTextarea('QmsgMsg', NULL,
            "评论人：{author}\n评论内容:\n{text}\n\n链接：{permalink}",
            _t("Qmsg酱通知模版"), _t("通过Qmsg酱通知您的内容模版，可使用变量列表见插件说明")
        );
        $form->addInput($QmsgMsg);
        $form->addItem(new Typecho_Widget_Helper_Layout('/div'));
        $form->addItem(new Typecho_Widget_Helper_Layout('/div'));
    }

    public static function checkQmsgchan(array $settings)
    {
        if (in_array('qmsg', $settings['setting'])) {
            if (empty($settings['QmsgKey'])) {
                return _t('请填写QmsgKEY');
            }
            if (empty($settings['QmsgMsg'])) {
                return _t('请填写Qmsg酱通知模版');
            }
        }
        return '';
    }

    public static function SMTP(Typecho_Widget_Helper_Form $form)
    {
        $form->addItem(new MDTitle('SMTP 配置', NULL, false));
        $host = new MDText('host', NULL, '',
            _t('邮件服务器地址'), _t('请填写 SMTP 服务器地址'));
        $form->addInput($host);

        $port = new MDText('port', null, 465,
            _t('端口号'), _t('端口号必须是数字，一般为465'));
        $form->addInput($port->addRule('isInteger', _t('端口号必须是数字')));

        $ssl = new MDSelect('secure',
            array('tls' => 'tls', 'ssl' => 'ssl'), 'ssl',
            _t('连接加密方式'));
        $form->addInput($ssl);

        $auth = new MDRadio('auth',
            array(1 => '是', 0 => '否'), 1,
            _t('启用身份验证'), _t('勾选后必须填写用户名和密码两项'));
        $form->addInput($auth);

        $user = new MDText('user', NULL,
            '', _t('用户名'), _t('启用身份验证后有效，一般为 name@domain.com '));
        $form->addInput($user);

        $pwd = new MDText('password', NULL,
            '', _t('密码'), _t('启用身份验证后有效，有些服务商可能需要专用密码，详询服务商客服'));
        $form->addInput($pwd);

        $from = new MDText('from', NULL,
            '', _t('发信人邮箱'));
        $form->addInput($from->addRule('email', _t('请输入正确的邮箱地址')));

        $from_name = new MDText('from_name', NULL,
            Helper::options()->title, _t('发信人名称'), _t('默认为站点标题'));
        $form->addInput($from_name);


        $titleForOwner = new MDText('titleForOwner', null,
            "[{title}] 一文有新的评论", _t('博主接收邮件标题'));
        $form->addInput($titleForOwner->addRule('required', _t('博主接收邮件标题 不能为空')));

        $titleForGuest = new MDText('titleForGuest', null,
            "您在 [{title}] 的评论有了回复", _t('访客接收邮件标题'));
        $form->addInput($titleForGuest->addRule('required', _t('访客接收邮件标题 不能为空')));

        $titleForApproved = new MDText('titleForApproved', null,
            "您在 [{title}] 的评论已被审核通过", _t('访客接收邮件标题'));
        $form->addInput($titleForApproved->addRule('required', _t('访客接收邮件标题 不能为空')));

        $form->addItem(new Typecho_Widget_Helper_Layout('/div'));
        $form->addItem(new Typecho_Widget_Helper_Layout('/div'));
    }

    public static function checkSMTP(array $settings)
    {
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
        return '';
    }

    public static function check(array $settings)
    {
        $s = self::checkServerchan($settings);
        if ($s != '')
            return $s;

        $s = self::checkQmsgchan($settings);
        if ($s != '')
            return $s;
        $s = self::checkSMTP($settings);
        if ($s != '')
            return $s;
        return '';
    }
}