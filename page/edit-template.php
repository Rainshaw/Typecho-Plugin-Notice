<?php
include "header.php";
include "menu.php";
Typecho\Widget::widget('Notice_libs_TestAction')->to($files);
?>

    <div class="main">
        <div class="body container">
            <div class="typecho-page-title">
                <h2><?= $files->getTitle(); ?></h2>
            </div>
            <div class="row typecho-page-main" role="main">
                <div class="typecho-edit-theme">
                    <div class="col-mb-12 col-tb-8 col-9 content">
                        <form method="post" name="theme" id="theme"
                              action="<?php $options->index('/action/' . TypechoPlugin\Notice\Plugin::$action_edit_template); ?>">
                            <label for="content" class="sr-only"><?php _e('编辑源码'); ?></label>
                            <textarea name="content" id="content" class="w-100 mono"
                                      <?php if (!$files->currentIsWriteable()): ?>readonly<?php endif; ?>><?php echo $files->currentContent(); ?></textarea>
                            <p class="submit">
                                <?php if ($files->currentIsWriteable()): ?>
                                    <input type="hidden" name="do" value="edit_theme"/>
                                    <input type="hidden" name="file" value="<?php echo $files->currentFile(); ?>"/>
                                    <button type="submit" class="btn primary"><?php _e('保存文件'); ?></button>
                                <?php else: ?>
                                    <em><?php _e('此文件无法写入'); ?></em>
                                <?php endif; ?>
                            </p>
                        </form>
                    </div>
                    <ul class="col-mb-12 col-tb-4 col-3">
                        <li><strong>模板文件</strong></li>
                        <?php while ($files->next()): ?>
                            <li<?php if ($files->current): ?> class="current"<?php endif; ?>>
                                <a href="<?php $options->adminUrl('extending.php?panel=' . TypechoPlugin\Notice\Plugin::$panel_edit_template . '&file=' . $files->file); ?>"><?php $files->file(); ?></a>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>

<?php
include 'copyright.php';
include 'common-js.php';
include 'footer.php';
