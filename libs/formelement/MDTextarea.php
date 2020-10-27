<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * 多行文字域帮手
 *
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 * @version $Id$
 */

/**
 * 多行文字域帮手类
 *
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class MDTextarea extends Typecho_Widget_Helper_Form_Element
{
    public function start()
    {
    }

    public function end()
    {
        echo '</ul></div></div></div>';
    }


    public function __construct($name = NULL, array $options = NULL, $value = NULL, $label = NULL, $description = NULL, $isOpen = true)
    {
        if ($isOpen) {
            $this->addItem(new MDCustomLabel('<div class="mdui-panel" mdui-panel=""><div class="mdui-panel-item mdui-panel-item-open"><div class="mdui-panel-item-header">' . $label . '</div><div class="mdui-panel-item-body"><ul style="padding-left: 0px; list-style: none!important" id="typecho-option-item-' . $name . '-' . self::$uniqueId . '">'));

        } else {
            $this->addItem(new MDCustomLabel('<div class="mdui-panel" mdui-panel=""><div class="mdui-panel-item"><div class="mdui-panel-item-header">' . $label . '</div><div class="mdui-panel-item-body"><ul style="padding-left: 0px; list-style: none!important" id="typecho-option-item-' . $name . '-' . self::$uniqueId . '">'));

        }

        $this->name = $name;
        self::$uniqueId++;

        /** 运行自定义初始函数 */
        $this->init();

        /** 初始化表单项 */
        $this->input = $this->input($name, $options);

        /** 初始化表单值 */
        if (NULL !== $value) {
            $this->value($value);
        }

        /** 初始化表单描述 */
        if (NULL !== $description) {
            $this->description($description);
        }
    }


    /**
     * 初始化当前输入项
     *
     * @access public
     * @param string $name 表单元素名称
     * @param array $options 选择项
     * @return Typecho_Widget_Helper_Layout
     */
    public function input($name = NULL, array $options = NULL)
    {
        $this->addItem(new MDCustomLabel('<div class="mdui-textfield">'));
        $input = new Typecho_Widget_Helper_Layout('textarea', array('id' => $name . '-0-' . self::$uniqueId, 'name' => $name, 'class' => 'mdui-textfield-input'));
        $this->addItem(new MDCustomLabel("</div>"));
        $this->container($input->setClose(false));
        $this->inputs[] = $input;

        return $input;
    }

    /**
     * 设置表单项默认值
     *
     * @access protected
     * @param string $value 表单项默认值
     * @return void
     */
    protected function _value($value)
    {
        $this->input->html(htmlspecialchars($value));
    }
}
