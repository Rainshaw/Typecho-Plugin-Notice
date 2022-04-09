<?php
namespace TypechoPlugin\Notice\libs;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

use Typecho;
use Utils;
use Widget;
use PHPMailer\PHPMailer;

use TypechoPlugin\Notice;

class TestAction extends Typecho\Widget implements Widget\ActionInterface
{

    private Widget\Options $_option;
    private $_pluginOption;
    private string $_template_dir;
    private $_currentFile;

    /**
     * 执行函数
     *
     * @access public
     * @return void
     * @throws Typecho\Widget\Exception
     * @throws Typecho\Exception
     */
    public function execute()
    {
        /** 管理员权限 */
        $this->widget('Widget_User')->pass('administrator');
        $this->_template_dir = Utils\Helper::options()->pluginDir() . '/Notice/template';
        $files = glob($this->_template_dir . '/*.{html,HTML}', GLOB_BRACE);
        $this->_currentFile = $this->request->get('file', 'owner.html');

        if (preg_match("/^([_0-9a-z-\.\ ])+$/i", $this->_currentFile)
            && file_exists($this->_template_dir . '/' . $this->_currentFile)) {
            foreach ($files as $file) {
                if (file_exists($file)) {
                    $file = basename($file);
                    $this->push(array(
                        'file' => $file,
                        'current' => ($file == $this->_currentFile)
                    ));
                }
            }

            return;
        }

        throw new Typecho\Widget\Exception('模板文件不存在', 404);
    }

    /**
     * 获取标题
     *
     * @access public
     * @return string
     */
    public function getTitle(): string
    {
        return _t('编辑邮件模版 %s', $this->_currentFile);
    }


    /**
     * 获取文件内容
     *
     * @access public
     * @return string
     */
    public function currentContent(): string
    {
        return htmlspecialchars(file_get_contents($this->_template_dir . '/' . $this->_currentFile));
    }

    /**
     * 获取文件是否可读
     *
     * @access public
     * @return string
     */
    public function currentIsWriteable(): string
    {
        return is_writeable($this->_template_dir . '/' . $this->_currentFile);
    }

    /**
     * 获取当前文件
     *
     * @access public
     * @return string
     */
    public function currentFile(): string
    {
        return $this->_currentFile;
    }

    /**
     * 邮件测试表单
     * @param $type string
     * @return Typecho\Widget\Helper\Form
     */
    public function testForm(string $type): Typecho\Widget\Helper\Form
    {
        /** 构建表单 */
        $options = Typecho\Widget::widget('Widget_Options');
        $action = array(
            'mail' => 'send_test_mail',
            'qmsg' => 'send_test_qmsgchan',
            'serverchan' => 'send_test_serverchan'
        );
        $form = new Typecho\Widget\Helper\Form(Typecho\Common::url('/action/' . Notice\Plugin::$action_test . '?do=' . $action[$type], $options->index),
            Typecho\Widget\Helper\Form::POST_METHOD);

        $title = new Typecho\Widget\Helper\Form\Element\Text('title', NULL, '测试文章标题', _t('title'), _t('被评论文章标题'));
        $form->addInput($title->addRule('required', '必须填写文章标题'));

        $author = new Typecho\Widget\Helper\Form\Element\Text('author', NULL, '测试评论者', _t('author'), _t('评论者名字'));
        $form->addInput($author->addRule('required', '必须填写评论者名字'));

        $mail = new Typecho\Widget\Helper\Form\Element\Text('mail', NULL, NULL, _t('mail'), _t('评论者邮箱'));
        $form->addInput($mail->addRule('required', '必须填写评论者邮箱')->addRule('email', _t('邮箱地址不正确')));

        $ip = new Typecho\Widget\Helper\Form\Element\Text('ip', NULL, '1.1.1.1', _t('ip'), _t('评论者ip'));
        $form->addInput($ip->addRule('required', '必须填写评论者ip'));

        $text = new Typecho\Widget\Helper\Form\Element\Textarea('text', NULL, '测试评论内容_(:з」∠)_', _t('text'), _t('评论内容'));
        $form->addInput($text->addRule('required', '必须填写评论内容'));

        $author_p = new Typecho\Widget\Helper\Form\Element\Text('author_p', NULL, NULL, _t('author_p'), _t('被评论者名字'));
        $form->addInput($author_p);

        $text_p = new Typecho\Widget\Helper\Form\Element\Textarea('text_p', NULL, NULL, _t('被评论内容'));
        $form->addInput($text_p);

        $permalink = new Typecho\Widget\Helper\Form\Element\Text('permalink', NULL, Helper::options()->index, _t('permalink'), _t('评论链接'));
        $form->addInput($permalink);

        $status = new Typecho\Widget\Helper\Form\Element\Select('status', array(
            "通过"=>"通过", "待审"=>"待审", "垃圾"=>"垃圾"), "待审", 'status', _t('评论状态'));
        $form->addInput($status);

        if ($type == 'mail') {
            $toName = new Typecho\Widget\Helper\Form\Element\Text('toName', NULL,
                '', _t('收件人名称'));
            $form->addInput($toName->addRule('required', '必须填写接收人名称'));

            $to = new Typecho\Widget\Helper\Form\Element\Text('to', NULL,
                '', _t('收件人邮箱'));
            $form->addInput($to->addRule('required', '必须填写接收邮箱')->addRule('email', _t('邮箱地址不正确')));

            $template = new Typecho\Widget\Helper\Form\Element\Select('template', array(
                'owner' => 'owner',
                'guest' => 'guest',
                'approved' => 'approved'
            ), 'owner', 'template', '选择发信的模版');
            $form->addInput($template);
        }

        $time = new Typecho\Date();
        $time = $time->timeStamp;
        $time = new Typecho\Widget\Helper\Form\Element\Hidden('time', NULL, $time);
        $form->addInput($time);

        $submit = new Typecho\Widget\Helper\Form\Element\Submit();
        $submit->input->setAttribute('class', 'btn primary');
        $form->addItem($submit);
        $submit->value('测试');


        return $form;
    }

    private function getArray(): array
    {
        $form = $this->request->from('title', 'author', 'mail', 'ip', 'text', 'author_p', 'text_p', 'permalink', 'status', 'time');
        $date = new Typecho\Date($form['time']);

        return array(
            $this->_option->title,
            $form['title'],
            $form['author'],
            $form['author_p'],
            $form['ip'],
            $form['mail'],
            $form['permalink'],
            $this->_option->siteUrl . __TYPECHO_ADMIN_DIR__ . "manage-comments.php",
            $form['text'],
            $form['text_p'],
            $date->format('Y-m-d H:i:s'),
            $form['status']
        );
    }

    /**
     * @throws Typecho\Widget\Exception
     */
    private function replace($type)
    {
        $msg = '';
        switch ($type) {
            case 'serverchan':
                $msg = $this->_pluginOption->scMsg;
                break;
            case 'qmsg':
                $msg = $this->_pluginOption->QmsgMsg;
                break;
            case 'mail':
                switch ($this->request->from('template')['template']) {
                    case 'owner':
                        $msg = Notice\libs\ShortCut::getTemplate('owner');
                        break;
                    case 'guest':
                        $msg = Notice\libs\ShortCut::getTemplate('guest');
                        break;
                    case 'approved':
                        $msg = Notice\libs\ShortCut::getTemplate('approved');
                        break;
                }
                break;
        }
        $replace = self::getArray();
        return Notice\libs\ShortCut::replaceArray($msg, $replace);
    }

    /**
     * @throws Typecho\Db\Exception
     * @throws Typecho\Widget\Exception
     */
    public function sendTestServerchan()
    {
        if (Typecho\Widget::widget('Notice_libs_TestAction')->testForm('serverchan')->validate()) {
            $this->response->goBack();
        }
        $msg = self::replace('serverchan');
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
        $result = file_get_contents('https://sc.ftqq.com/' . $this->_pluginOption->scKey . '.send', false, $context);
        /** 日志 */
        Notice\libs\DB::log('0', 'wechat', "测试\n" . $result . "\n\n" . $msg);
        $result = json_decode($result, true);
        /** 提示信息 */
        $this->widget('Widget_Notice')->set(0 === $result['errno'] ? _t('发送成功') : _t('发送失败：' . $result['errmsg']),
            0 === $result['errno'] ? 'success' : 'notice');

        /** 转向原页 */
        $this->response->goBack();
    }

    /**
     * @throws Typecho\Db\Exception
     * @throws Typecho\Widget\Exception
     */
    public function sendTestQmsgchan()
    {
        if (Typecho\Widget::widget('Notice_libs_TestAction')->testForm('qmsg')->validate()) {
            $this->response->goBack();
        }
        $msg = self::replace('qmsg');
        if ($this->_pluginOption->QmsgQQ == NULL) {
            $post_data = http_build_query(
                array(
                    'msg' => $msg
                )
            );
        } else {
            $post_data = http_build_query(
                array(
                    'msg' => $msg,
                    'qq' => $this->_pluginOption->QmsgQQ
                )
            );
        }

        $opts = array('http' =>
            array(
                'method' => 'POST',
                'header' => 'Content-type: application/x-www-form-urlencoded',
                'content' => $post_data
            )
        );
        $context = stream_context_create($opts);
        $result = file_get_contents('https://qmsg.zendee.cn/send/' . $this->_pluginOption->QmsgKey, false, $context);
        /** 日志 */
        Notice\libs\DB::log('0', 'qq', "测试\n" . $result . "\n\n" . $msg);
        $result = json_decode($result, true);
        /** 提示信息 */
        $this->widget('Widget_Notice')->set(true === $result['success'] ? _t('发送成功') : _t('发送失败：' . $result),
            true === $result['success'] ? 'success' : 'notice');

        /** 转向原页 */
        $this->response->goBack();
    }

    /**
     * @throws PHPMailer\Exception
     * @throws Typecho\Db\Exception
     * @throws Typecho\Widget\Exception
     */
    public function sendTestMail()
    {
        if (Typecho\Widget::widget('Notice_libs_TestAction')->testForm('mail')->validate()) {
            $this->response->goBack();
        }
        $msg = self::replace('mail');

        $mail = new PHPMailer\PHPMailer(false);
        $mail->isSMTP();
        $mail->Host = $this->_pluginOption->host;
        $mail->SMTPAuth = !!$this->_pluginOption->auth;
        $mail->Username = $this->_pluginOption->user;
        $mail->Password = $this->_pluginOption->password;
        $mail->SMTPSecure = $this->_pluginOption->secure;
        $mail->Port = $this->_pluginOption->port;
        $mail->getSMTPInstance()->setTimeout(10);
        $mail->isHTML(true);
        $mail->CharSet = 'utf-8';
        $mail->setFrom($this->_pluginOption->from, $this->_pluginOption->from_name);
        var_dump($this->request->get('to'));
        $mail->addAddress($this->request->get('to'), $this->request->getArray('toName'));
        $mail->Body = $msg;


        switch ($this->request->from('template')['template']) {
            case 'owner':
                $mail->Subject = Notice\libs\ShortCut::replaceArray($this->_pluginOption->titleForOwner, self::getArray());
                $mail->AltBody = "作者：" .
                    $this->request->get('author') . "\r\n链接：" .
                    $this->request->get('permalink') .
                    "\r\n评论：\r\n" .
                    $this->request->get('text');
                break;
            case 'guest':
                $mail->Subject = Notice\libs\ShortCut::replaceArray($this->_pluginOption->titleForGuest, self::getArray());
                $mail->AltBody = "作者：" .
                    $this->request->get('author') .
                    "\r\n链接：" .
                    $this->request->get('permalink') .
                    "\r\n评论：\r\n" .
                    $this->request->get('text');
                break;
            case 'approved':
                $mail->Subject = Notice\libs\ShortCut::replaceArray($this->_pluginOption->titleForApproved, self::getArray());
                $mail->AltBody = "您的评论已通过审核。\n";
                break;
        }

        $result = $mail->send();
        /** 日志 */
        Notice\libs\DB::log('0', 'mail', "测试\n" . $result . "\n\n" . $msg);
        /** 提示信息 */
        $this->widget('Widget_Notice')->set(true === $result ? _t('发送成功') : _t('发送失败：' . $result),
            true === $result ? 'success' : 'notice');

        /** 转向原页 */
        $this->response->goBack();


    }

    /**
     * 编辑模板文件
     * @param $file
     * @throws Typecho\Widget\Exception
     */
    public function editTheme($file)
    {
        $path = Utils\Helper::options()->pluginDir() . '/Notice/template/' . $file;
        if (file_exists($path) && is_writeable($path)) {
            $handle = fopen($path, 'wb');
            if ($handle && fwrite($handle, $this->request->content)) {
                fclose($handle);
                $this->widget('Widget_Notice')->set(_t("文件 %s 的更改已经保存", $file), 'success');
            } else {
                $this->widget('Widget_Notice')->set(_t("文件 %s 无法被写入", $file), 'error');
            }
            $this->response->goBack();
        } else {
            throw new Typecho\Widget\Exception(_t('您编辑的模板文件不存在'));
        }
    }

    /**
     * @throws Typecho\Plugin\Exception
     */
    public function init()
    {
        $this->_option = Utils\Helper::options();
        $this->_pluginOption = Utils\Helper::options()->plugin('Notice');
    }

    /**
     * @throws PHPMailer\Exception
     * @throws Typecho\Db\Exception
     * @throws Typecho\Widget\Exception
     * @throws Typecho\Plugin\Exception
     */
    public function action()
    {
        Typecho\Widget::widget('Widget_User')->pass('administrator');
        $this->init();
        $this->on($this->request->is('do=send_test_serverchan'))->sendTestServerchan();
        $this->on($this->request->is('do=send_test_qmsgchan'))->sendTestQmsgchan();
        $this->on($this->request->is('do=send_test_mail'))->sendTestMail();
        $this->on($this->request->is('do=edit_theme'))->editTheme($this->request->file);
    }

}
