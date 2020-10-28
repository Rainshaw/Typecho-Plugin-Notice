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
        $db = Typecho_Db::get();
        if ($db->fetchRow($db->select()->from('table.options')->where('name = ?', 'plugin:Notice-Backup'))) {
            $backupExist = '<div class="mdui-chip"><span class="mdui-chip-icon mdui-color-green"><i class="mdui-icon material-icons">backup</i></span><span
        class="mdui-chip-title mdui-text-color-light-blue">数据库中存在插件配置备份</span></div>';
        } else {
            $backupExist = '<div class="mdui-chip"><span class="mdui-chip-icon mdui-color-red"><i class="mdui-icon material-icons">backup</i></span><span 
        class="mdui-chip-title mdui-text-color-red">数据库没有插件配置备份</span></div>';
        }
        $tag = Version::getNewRelease();
        $tag_compare = version_compare(__TYPECHO_PLUGIN_NOTICE_VERSION__, $tag);
        if ($tag_compare<0){
            $update = '<div class="mdui-chip"><span class="mdui-chip-icon mdui-color-red"><i class="mdui-icon material-icons">system_update_alt</i></span>
                <span class="mdui-chip-title mdui-text-color-red">新版本'.$tag.'已可用</span></div>';
        }elseif ($tag_compare==0){
            $update = '<div class="mdui-chip"><span class="mdui-chip-icon mdui-color-green"><i class="mdui-icon material-icons">cloud_done</i></span>
                <span class="mdui-chip-title mdui-text-color-light-blue">当前是最新版本</span></div>';
        }else{
            $update = '<div class="mdui-chip"><span class="mdui-chip-icon mdui-color-amber"><i class="mdui-icon material-icons">warning</i></span>
                <span class="mdui-chip-title mdui-text-color-cyan">您当前正在使用测试版</span></div>';
        }

        echo <<<EOF
<div class="mdui-card">
  <div class="mdui-card-media">
    <img src="https://ae01.alicdn.com/kf/H07926922932545d993267d5c1ab5b9276.jpg"/>
    <div class="mdui-card-media-covered mdui-card-media-covered-transparent">
      <div class="mdui-card-primary">
        <div class="mdui-card-primary-title">Notice</div>
        <div class="mdui-card-primary-subtitle">欢迎使用 Notice 插件</div>
      </div>
    </div>
  </div>
  
  <div class="mdui-card-content">
  {$update}
  {$backupExist}
  </div>
  <div class="mdui-card-actions">
    <button class="mdui-btn mdui-ripple" mdui-tooltip="{content: '唯一指定发布源'}"><a href = "https://github.com/RainshawGao/Typecho-Plugin-Notice">Github</a></button>
    <button class="mdui-btn mdui-ripple" mdui-tooltip="{content: '欢迎来踩博客～'}"><a href = "https://blog.ruixiaolu.com/">作者博客</a></button>
    <button class="mdui-btn mdui-ripple showSettings" mdui-tooltip="{content: '展开所有设置后，使用 ctrl + F 可以快速搜索某一设置项'}">展开所有设置</button>
    <button class="mdui-btn mdui-ripple hideSettings">折叠所有设置</button>
    <button class = "mdui-btn mdui-ripple recover_backup" mdui-tooltip="{content: '从数据库插件配置备份恢复数据'}">从备份恢复配置</button>
    <button class = "mdui-btn mdui-ripple backup" mdui-tooltip="{content: '1. 仅仅是备份Notice的设置</br>2. 禁用插件的时候，设置数据会清空但是备份设置不会被删除。</br>3. 所以当你重启启用插件时，可以恢复备份设置。</br>4. 备份设置同样是备份到数据库中。</br>5. 如果已有备份设置，再次备份会覆盖之前备份<br/>6. 插件开发过程中会尽量保证配置项不发生较大改变～'}">备份插件配置</button>
    <button class = "mdui-btn mdui-ripple del_backup" mdui-tooltip="{content:'删除handsome备份数据'}">删除现有Notice插件配置备份</button>
  </div>
  
</div>
EOF;

    }

    public static function script(Typecho_Widget_Helper_Form $form){

        $blog_url = Helper::options()->siteUrl;
        $action_url = $blog_url . 'action/' . Notice_Plugin::$action_setting;
        echo<<<EOF
<script>
    $(function(){
         $('.showSettings').bind('click',function() {
           $('.mdui-panel-item').addClass('mdui-panel-item-open');
         });
         $('.hideSettings').bind('click',function() {
            $('.mdui-panel-item').removeClass('mdui-panel-item-open');
         });
     });
    $('.backup').click(function() {
         mdui.confirm("确认要备份数据吗", "备份数据", function() {
           $.ajax({
            url: '$action_url',
            data: {"do":"backup"},
            success: function(data) {
                if (data !== "-1"){
                    mdui.snackbar({
                    message: '备份成功，操作码:' + data +',正在刷新页面……',
                    position: 'bottom'
                });
                    setTimeout(function (){
                    location.reload();
                },1000);
                }else {
                    mdui.snackbar({
                    message: '备份失败,错误码' + data,
                    position: 'bottom'
                });
                }
            }
        })
         },null , {"confirmText":"确认","cancelText":"取消"})

     });
     
     
     $('.del_backup').click(function() {
         
         mdui.confirm("确认要删除备份数据吗", "删除备份", function() {
            $.ajax({
            url: '$action_url',
            data: {"do":"del_backup"},
            success: function(data) {
                if (data !== "-1"){
                    mdui.snackbar({
                    message: '删除备份成功，操作码:' + data +',正在刷新页面……',
                    position: 'bottom'
                });
                    setTimeout(function (){
                    location.reload();
                },1000);
                }else {
                    var message = "没有备份，你删什么删，别问我为什么这么冲，因为总有问我为啥删除失败，对不起。";
                    mdui.snackbar({
                    message: message,
                    position: 'bottom'
                });
                }
            }
        })
},null , {"confirmText":"确认","cancelText":"取消"});
         
});
     
     $('.recover_backup').click(function() {
         
         
        mdui.confirm("确认要恢复备份数据吗", "恢复备份", function() {
    $.ajax({
        url: '$action_url',
        data: {"do":"recover_backup"},
        success: function(data) {
            if (data !== "-1"){
                mdui.snackbar({
                    message: '恢复备份成功，操作码:' + data +',正在刷新页面……',
                    position: 'bottom'
                });
                setTimeout(function (){
                    location.reload();
                },1000);
            }else {
                mdui.snackbar({
                    message: '恢复备份失败,错误码' + data,
                    position: 'bottom'
                });
            }
        }
    })

},null , {"confirmText":"确认","cancelText":"取消"})
     });
</script>
EOF;

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