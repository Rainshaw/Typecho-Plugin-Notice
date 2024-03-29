<?php

namespace TypechoPlugin\Notice\libs\FormElement;

if (!defined('__TYPECHO_ROOT_DIR__')) exit;

use Typecho;

/**
 * 文字输入表单项帮手类
 *
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class MDText extends Typecho\Widget\Helper\Form\Element
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
     * @param string|null $name 表单元素名称
     * @param array|null $options 选择项
     * @return Typecho\Widget\Helper\Layout
     */
    public function input(?string $name = null, ?array $options = null): ?Typecho\Widget\Helper\Layout
    {
        $this->addItem(new MDCustomLabel('<div class="mdui-textfield">'));
        $input = new Typecho\Widget\Helper\Layout('input', array('id' => $name . '-0-' . self::$uniqueId,
            'name' => $name, 'type' => 'text', 'class' => 'mdui-textfield-input'));
        $this->container($input);
        $this->addItem(new MDCustomLabel("</div>"));
        $this->inputs[] = $input;

        return $input;
    }

    /**
     * 设置表单项默认值
     *
     * @access protected
     * @param mixed $value 表单项默认值
     * @return void
     */
    protected function inputValue($value)
    {
        $this->input->setAttribute('value', htmlspecialchars($value));
    }
}
