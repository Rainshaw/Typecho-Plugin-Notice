<?php
include "header.php";
include "menu.php";
$current = $request->get('type', 'mail');
$title = $current == 'mail' ? 'Notice 插件邮件配置测试' :
    ($current == 'qmsg'? 'Notice 插件Qmsg酱配置测试': 'Notice 插件Server酱配置测试')
?>

    <div class="main">
        <div class="body container">
            <div class="typecho-page-title">
                <h2><?=$title?></h2>
            </div>
            <div class="row typecho-page-main" role="main">
                <div class="col-mb-12">
                    <ul class="typecho-option-tabs fix-tabs clearfix">
                        <li<?=($current == 'mail' ? ' class="current"' : '')?>><a href="<?php $options->adminUrl('extending.php?panel=' . TypechoPlugin\Notice\Plugin::$panel_test . '&type=mail'); ?>">
                                <?php _e('邮件发送测试'); ?></a></li>
                        <li<?=($current == 'theme' ? ' class="current"' : '')?>><a href="<?php $options->adminUrl('extending.php?panel=' . TypechoPlugin\Notice\Plugin::$panel_test . '&type=qmsg'); ?>">
                                <?php _e('Qmsg酱发送测试'); ?></a></li>
                        <li<?=($current == 'theme' ? ' class="current"' : '')?>><a href="<?php $options->adminUrl('extending.php?panel=' . TypechoPlugin\Notice\Plugin::$panel_test . '&type=serverchan'); ?>">
                                <?php _e('Server酱发送测试'); ?></a></li>
                    </ul>
                </div>
                <div class="typecho-edit-theme">
                    <div class="col-mb-12 col-tb-8 col-9 content">
                        <?php Typecho\Widget::widget('Notice_libs_TestAction')->testForm($current)->render(); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php
include 'copyright.php';
include 'common-js.php';
include 'footer.php';
?>
