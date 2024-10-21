<?php

namespace TypechoPlugin\Notice;
if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

require_once "libs/Config.php";
require_once "libs/db.php";
require_once "libs/Utils.php";
require_once "libs/FormElement/MDFormElements.php";
require_once "libs/phpmailer/PHPMailer.php";
require_once "libs/phpmailer/Exception.php";
require_once "libs/phpmailer/SMTP.php";

use Typecho;
use Typecho\Plugin\PluginInterface;
use Utils;
use Widget;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

const __TYPECHO_PLUGIN_NOTICE_VERSION__ = '1.0.6';

/**
 * <strong style="color:#28B7FF;font-family: 楷体;">评论通知</strong>
 *
 * @package Notice
 * @author <strong style="color:#28B7FF;font-family: 楷体;">Rainshaw</strong>
 * @version 1.0.6
 * @link https://github.com/Rainshaw
 * @since 1.2.0
 */
class Plugin implements PluginInterface
{
    /** @var string 插件配置action前缀 */
    public static string $action_setting = 'Plugin-Notice-Setting';

    /** @var string 插件测试action前缀 */
    public static string $action_test = 'Plugin-Notice-Test';

    /** @var string 编辑插件模版action前缀 */
    public static string $action_edit_template = 'Plugin-Notice-Edit-Template';

    /** @var string 插件编辑模板面板 */
    public static string $panel_edit_template = 'Notice/page/edit-template.php';

    /** @var string 插件测试面板 */
    public static string $panel_test = 'Notice/page/test.php';

    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     *
     * @access public
     * @return string
     * @throws Typecho\Plugin\Exception
     * @throws Typecho\Db\Exception
     */
    public static function activate(): string
    {
        $res = '<div id="typecho-plugin-notice-active-box" style="border-radius:2px;box-shadow:1px 1px 50px rgba(0,0,0,.3);background-color: #fff;width: auto; height: auto; z-index: 2501554; position: fixed; margin-left: -125px; margin-top: -75px; left: 50%; top: 50%;">
                    <div style="text-align:center;height:42px;line-height:42px;border-bottom:1px solid #eee;font-size:14px;overflow:hidden;border-radius:2px 2px 0 0;font-weight:bold;position:relative;cursor:move;min-width:200px;box-sizing:border-box;background-color:#28B7FF;color:#fff;">';
        $res .= libs\DB::dbInstall();

        $res .= '   </div>
                    <div style="padding:15px;font-size:14px;min-width:150px;position:relative;box-sizing:border-box;height:50px;">
                        欢迎使用Notice插件，希望能让您喜欢！
                    </div>
                    <div style="text-align:right;padding-bottom:15px;padding-right:10px;min-width:200px;box-sizing:border-box;">
                        <button onclick="colseDIV()"style="height:28px;line-height:28px;margin:15px 5px 0;padding:0 15px;border-radius:2px;font-weight:400;cursor:pointer;text-decoration:none;outline:none;background-color:#28B7FF;border:0;color:#fff;">
                            关闭
                        </button>
                    </div>
                    <script>function colseDIV(){$("#typecho-plugin-notice-active-box").hide()}</script>
                </div>';

        // 通知触发函数
        Typecho\Plugin::factory('Widget_Feedback')->finishComment = __CLASS__ . '::requestService';
        Typecho\Plugin::factory('Widget_Comments_Edit')->finishComment = __CLASS__ . '::requestService';
        Typecho\Plugin::factory('Widget_Comments_Edit')->mark = __CLASS__ . '::approvedMail';
        // 注册异步调用函数
        Typecho\Plugin::factory('Widget_Service')->sendSC = __CLASS__ . '::sendSC';
        Typecho\Plugin::factory('Widget_Service')->sendQmsg = __CLASS__ . '::sendQmsg';
        Typecho\Plugin::factory('Widget_Service')->sendMail = __CLASS__ . '::sendMail';
        Typecho\Plugin::factory('Widget_Service')->sendApprovedMail = __CLASS__ . '::sendApprovedMail';

        Utils\Helper::addAction(self::$action_setting, 'TypechoPlugin\Notice\libs\SettingAction');
        Utils\Helper::addAction(self::$action_test, 'TypechoPlugin\Notice\libs\TestAction');
        Utils\Helper::addAction(self::$action_edit_template, 'TypechoPlugin\Notice\libs\TestAction');
        $index = Utils\Helper::addMenu("Notice");
        Utils\Helper::addPanel($index, self::$panel_edit_template, '编辑邮件模版', '', 'administrator');
        Utils\Helper::addPanel($index, self::$panel_test, '配置测试', '', 'administrator');


        return $res;
    }

    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     *
     * @static
     * @access public
     * @return string
     * @throws Typecho\Db\Exception
     * @throws Typecho\Exception
     * @throws Typecho\Plugin\Exception
     */
    public static function deactivate(): string
    {
        Utils\Helper::removeAction(self::$action_setting);
        Utils\Helper::removeAction(self::$action_test);
        Utils\Helper::removeAction(self::$action_edit_template);
        $index = Utils\Helper::removeMenu("Notice");
        Utils\Helper::removePanel($index, self::$panel_edit_template);
        Utils\Helper::removePanel($index, self::$panel_test);

        $delDB = Utils\Helper::options()->plugin('Notice')->delDB;
        if ($delDB == 1) {
            $s = libs\DB::dbUninstall();
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
     * @param Typecho\Widget\Helper\Form $form 配置面板
     * @return void
     */
    public static function config(Typecho\Widget\Helper\Form $form)
    {
        // CSS
        libs\Config::style($form);
        // header
        libs\Config::header($form);
        // 配置开始
        $form->addItem(new libs\FormElement\MDCustomLabel('<div class="mdui-panel" mdui-panel="">'));
        {
            // 插件配置
            libs\Config::Setting($form);

            // Server 酱
            libs\Config::Serverchan($form);

            // Qmsg 酱
            libs\Config::Qmsgchan($form);

            // SMTP
            libs\Config::SMTP($form);
        }
        $form->addItem(new Typecho\Widget\Helper\Layout('/div'));
        // 美化提交按钮
        $submit = new Typecho\Widget\Helper\Form\Element\Submit(NULL, NULL, _t('保存设置'));
        $submit->input->setAttribute('class', 'mdui-btn mdui-color-theme-accent mdui-ripple submit_only');
        $form->addItem($submit);
        // javascript
        libs\Config::script($form);

    }

    /**
     * 个人用户的配置面板
     *
     * @access public
     * @param Typecho\Widget\Helper\Form $form
     * @return void
     */
    public static function personalConfig(Typecho\Widget\Helper\Form $form)
    {
    }

    /**
     * 检查参数
     *
     * @param array $settings
     * @return string
     */
    public static function configCheck(array $settings): string
    {
        return libs\Config::check($settings);
    }

    /**
     * 评论通知回调
     *
     * @access public
     * @param $comment
     * @return void
     * @throws Typecho\Plugin\Exception
     * @throws Typecho\Db\Exception
     */
    public static function requestService($comment)
    {
        libs\DB::log($comment->coid, 'log', '评论异步请求开始');
        $options = Utils\Helper::options()->plugin('Notice');
        if (in_array('mail', $options->setting) && !empty($options->host)) {
            libs\DB::log($comment->coid, "log", "调用发送邮件异步");
            self::sendMail($comment->coid);
        }
        if (in_array('serverchan', $options->setting) && !empty($options->scKey)) {
            libs\DB::log($comment->coid, "log", "调用Server酱异步");
            self::sendSC($comment->coid);
        }
        if (in_array('qmsg', $options->setting) && !empty($options->QmsgKey)) {
            libs\DB::log($comment->coid, "log", "调用Qmsg酱异步");
            self::sendQmsg($comment->coid);
        }
        libs\DB::log($comment->coid, 'log', '评论异步请求结束');
    }

    /**
     * 审核通过评论回调
     *
     * @access public
     * @param $comment
     * @param $edit
     * @param string $status
     * @return void
     * @throws Typecho\Db\Exception
     */
    public static function approvedMail($comment, $edit, $status)
    {
        libs\DB::log($comment['coid'], '评论通过异步请求开始', '');
        if ('approved' === $status) {
            self::sendApprovedMail($comment['coid']);
        }
        libs\DB::log($comment['coid'], '评论通过异步请求结束', '');
    }

    /**
     * 异步发送微信 Powered By Server酱
     *
     * @param integer $coid 评论ID
     * @return void
     * @throws Typecho\Db\Exception
     * @throws Typecho\Plugin\Exception
     * @access public
     */
    public static function sendSC(int $coid)
    {
        libs\DB::log($coid, 'log', 'Server酱：通知开始');
        $options = Utils\Helper::options();
        $pluginOptions = $options->plugin('Notice');
        $comment = Utils\Helper::widgetById('comments', $coid);
        if (empty($pluginOptions->scKey)) {
            libs\DB::log($coid, 'log', 'Server酱：缺少sckey');
            return;
        }
        $key = $pluginOptions->scKey;
        if (!$comment->have() || empty($comment->mail)) {
            libs\DB::log($coid, 'log', 'Server酱：评论缺少关键信息');
            return;
        }
        if ($comment->authorId == 1) {
            libs\DB::log($coid, 'log', 'Server酱：博主评论，跳过');
            return;
        }

        $msg = $pluginOptions->scMsg;
        $msg = libs\ShortCut::replace($msg, $coid);

        $post_data = http_build_query(
            array(
                'text' => "有人在您的博客发表了评论",
                'desp' => $msg
            )
        );

        $opts = array('http' =>
            array(
                'method' => 'POST',
                'header' => 'Content-type: application/x-www-form-urlencoded',
                'content' => $post_data
            )
        );
        $context = stream_context_create($opts);
        $result = file_get_contents('https://sctapi.ftqq.com/' . $key . '.send', false, $context);

        libs\DB::log($coid, 'wechat', $result . "\n\n" . $msg);
        libs\DB::log($coid, 'log', 'Server酱：通知结束');
    }

    /**
     * 异步发送QQ Powered By Qmsg 酱
     *
     * @param integer $coid 评论ID
     * @return void
     * @throws Typecho\Db\Exception
     * @throws Typecho\Plugin\Exception
     * @access public
     */
    public static function sendQmsg(int $coid)
    {
        libs\DB::log($coid, 'log', 'Qmsg酱：通知开始');
        $options = Utils\Helper::options();
        $pluginOptions = $options->plugin('Notice');
        $comment = Utils\Helper::widgetById('comments', $coid);
        if (empty($pluginOptions->QmsgKey)) {
            libs\DB::log($coid, 'log', 'Qmsg酱：评论缺少qmsgkey');
            return;
        }
        $key = $pluginOptions->QmsgKey;
        if (!$comment->have() || empty($comment->mail)) {
            libs\DB::log($coid, 'log', "Qmsg酱：评论缺少关键信息");
            return;
        }
        if ($comment->authorId == 1) {
            libs\DB::log($coid, 'log', "Qmsg酱：博主评论，跳过");
            return;
        }

        $msg = $pluginOptions->QmsgMsg;
        $msg = libs\ShortCut::replace($msg, $coid);

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

        libs\DB::log($coid, 'qq', $result . "\n\n" . $msg);
        libs\DB::log($coid, 'log', 'Qmsg酱：通知结束');
    }

    /**
     * @throws PHPMailerException
     */
    public static function checkMailConfig($pluginOptions, $comment): ?PHPMailer
    {
        if (!in_array('mail', $pluginOptions->setting)) {
            return null;
        }

        if (empty($pluginOptions->host)) {
            return null;
        }

        if (!$comment->have() || empty($comment->mail)) {
            return null;
        }

        $mail = new PHPMailer(false);

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
        return $mail;
    }

    /**
     * 异步发送通知邮件
     *
     * @access public
     * @param int $coid 评论id
     * @return void
     * @throws Typecho\Plugin\Exception
     * @throws Typecho\Widget\Exception
     * @throws PHPMailerException
     * @throws Typecho\Db\Exception
     */
    public static function sendMail(int $coid)
    {
        libs\DB::log($coid, 'log', '邮件：发送开始');
        $pluginOptions = Utils\Helper::options()->plugin('Notice');
        $comment = Utils\Helper::widgetById('comments', $coid);
        assert($comment instanceof Widget\Base\Comments);

        $mail = self::checkMailConfig($pluginOptions, $comment);
        if ($mail == null) {
            libs\DB::log($coid, 'log', '邮件：初始化异常，请检查插件配置');
            return;
        }

        if (0 == $comment->parent) {
            // 某文章或页面的新评论，向博主发信
            libs\DB::log($coid, 'log', '邮件：新评论');
            if ($comment->ownerId != $comment->authorId) {
                libs\DB::log($coid, 'log', '邮件：新评论：向博主发信');
                // 如果评论者不是文章作者自身，则发信
                $post = Utils\Helper::widgetById('contents', $comment->cid);
                assert($post instanceof Widget\Base\Contents);
                $mail->addAddress($post->author->mail, $post->author->name);
                // 构造邮件
                $mail->Subject = libs\ShortCut::replace($pluginOptions->titleForOwner, $coid);
                $mail->Body = libs\ShortCut::replace(libs\ShortCut::getTemplate("owner"), $coid);
                $mail->AltBody = "作者：" .
                    $comment->author . "\r\n链接：" .
                    $comment->permalink .
                    "\r\n评论：\r\n" .
                    $comment->text;
                $mail->send();
                libs\DB::log($coid, 'mail', $mail->Body);
            } else {
                libs\DB::log($coid, 'log', '邮件：新评论：文章作者评论，跳过');
            }
        } else {
            libs\DB::log($coid, 'log', '邮件：子评论');
            // 某评论有新的子评论，向父评论发信
            if ('approved' == $comment->status) {
                // 如果评论者之前有通过审核的评论，该评论会直接通过审核，则向父评论及文章作者发信
                libs\DB::log($coid, 'log', '邮件：子评论：通过审核');
                $parent = Utils\Helper::widgetById('comments', $comment->parent);
                assert($parent instanceof Widget\Base\Comments);
                if ($comment->authorId != $comment->ownerId) {
                    libs\DB::log($coid, 'log', '邮件：子评论：通过审核：文章作者评论');
                    if ($parent->authorId == $parent->ownerId) {
                        libs\DB::log($coid, 'log', '邮件：子评论：通过审核：文章作者评论：父评论作者为文章作者跳过发信');
                    } else {
                        libs\DB::log($coid, 'log', '邮件：子评论：通过审核：文章作者评论：给父评论发信');
                        $mail->addAddress($parent->mail, $parent->author);
                        $mail->Subject = libs\ShortCut::replace($pluginOptions->titleForGuest, $coid);
                        $mail->Body = libs\ShortCut::replace(libs\ShortCut::getTemplate('guest'), $coid);
                    }
                } else {
                    libs\DB::log($coid, 'log', '邮件：子评论：通过审核：游客评论');
                    if ($parent->authorId != $parent->ownerId) {
                        libs\DB::log($coid, 'log', '邮件：子评论：通过审核：游客评论：父评论作者非文章作者');
                        $post = Utils\Helper::widgetById('contents', $comment->cid);
                        assert($post instanceof Widget\Base\Contents);
                        $mail->addCC($post->author->mail, $post->author->name);
                    } else {
                        libs\DB::log($coid, 'log', '邮件：子评论：通过审核：游客评论：父评论作者为文章作者');
                    }
                    $mail->addAddress($parent->mail, $parent->author);
                    $mail->Subject = libs\ShortCut::replace($pluginOptions->titleForGuest, $coid);
                    $mail->Body = libs\ShortCut::replace(libs\ShortCut::getTemplate('guest'), $coid);
                }
            } elseif ($comment->status == "waiting") {
                // 评论标记为待审核，向博主发送评论通知
                libs\DB::log($coid, 'log', '邮件：子评论：待审核');
                $owner = Utils\Helper::widgetById("users", $comment->ownerId);
                assert($owner instanceof Widget\Base\Users);
                $mail->addAddress($owner->mail, $owner->name);
                $mail->Subject = libs\ShortCut::replace($pluginOptions->titleForOwner, $coid);
                $mail->Body = libs\ShortCut::replace(libs\ShortCut::getTemplate('owner'), $coid);
            }
            if (count($mail->getToAddresses()) > 0) {
                $mail->AltBody = "作者：" .
                    $comment->author .
                    "\r\n链接：" .
                    $comment->permalink .
                    "\r\n评论：\r\n" .
                    $comment->text;
                $mail->send();
                libs\DB::log($coid, 'mail', $mail->Body);
            }
        }
        libs\DB::log($coid, 'log', '邮件：发送结束');
    }

    /**
     * 异步发送评论通过审核邮件
     *
     * @access public
     * @param int $coid 评论id
     * @return void
     * @throws Typecho\Plugin\Exception
     * @throws Typecho\Widget\Exception
     * @throws PHPMailerException
     * @throws Typecho\Db\Exception
     */
    public static function sendApprovedMail(int $coid)
    {
        libs\DB::log($coid, 'log', '邮件：评论审核通过：开始');
        $pluginOptions = Utils\Helper::options()->plugin('Notice');
        $comment = Utils\Helper::widgetById('comments', $coid);
        assert($comment instanceof Widget\Base\Comments);

        $mail = self::checkMailConfig($pluginOptions, $comment);
        if ($mail == null) {
            libs\DB::log($coid, 'log', '邮件：评论审核通过：缺少关键参数，请检查插件配置');
            return;
        }
        // 向评论者发送审核通过邮件
        libs\DB::log($coid, 'log', "邮件：评论审核通过：向评论者发信");
        $mail->addAddress($comment->mail, $comment->author);
        $mail->Subject = libs\ShortCut::replace($pluginOptions->titleForApproved, $coid);
        $mail->Body = libs\ShortCut::replace(libs\ShortCut::getTemplate('approved'), $coid);
        $mail->AltBody = "您的评论已通过审核。\n";
        $mail->send();
        libs\DB::log($coid, 'mail', $mail->Body);

        // 向父评论发送通知邮件
        if ($comment->parent != 0) {
            $mail->clearAllRecipients();

            libs\DB::log($coid, 'log', "邮件：评论审核通过：有父评论");
            $parent = Utils\Helper::widgetById('comments', $comment->parent);
            assert($parent instanceof Widget\Base\Comments);
            if($parent->ownerId != $parent->authorId){
                libs\DB::log($coid, 'log', "邮件：评论审核通过：父评论作者非文章作者向父评论发信");
                $mail->addAddress($parent->mail, $parent->author);
                $mail->Subject = libs\ShortCut::replace($pluginOptions->titleForGuest, $coid);
                $mail->Body = libs\ShortCut::replace(libs\ShortCut::getTemplate('guest'), $coid);
                $mail->AltBody = "作者：" .
                    $comment->author .
                    "\r\n链接：" .
                    $comment->permalink .
                    "\r\n评论：\r\n" .
                    $comment->text;
                $mail->send();
                libs\DB::log($coid, 'mail', $mail->Body);
            }else{
                libs\DB::log($coid, 'log', "邮件：评论审核通过：父评论作者为文章作者跳过发信");
            }
        }
        libs\DB::log($coid, "log", "邮件：评论审核通过：结束");
    }
}
