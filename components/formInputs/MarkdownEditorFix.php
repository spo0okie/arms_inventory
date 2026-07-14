<?php

namespace app\components\formInputs;

use kartik\markdown\MarkdownEditor;
use Yii;
use yii\bootstrap5\Html;

/**
 * Обёртка над kartik MarkdownEditor (писан под BS3/BS4) для нашего стека BS5 + Font Awesome:
 * - isBs4() для BS5 возвращает false, из-за чего виджет строил дефолтный тулбар
 *   с именами глификонов BS3 (picture, fullscreen, floppy-disk, ...), которых нет
 *   в Font Awesome - иконки не отображались. Считаем BS4+ единой веткой рендера:
 *   берутся FA-имена иконок, dropdown-item у пунктов дропдаунов, без лишнего caret.
 * - дропдауны BS5 открываются по data-bs-toggle, родитель ставит только data-toggle.
 * - кнопка Preview перенесена из футера в верхний тулбар: отдельная группа справа,
 *   только иконка без label (JS плагина находит её по id, так что расположение не важно).
 * - float-right/pull-right в BS5 не работают - группам справа дополняется float-end.
 * - у части кнопок дефолтного тулбара icon/label = null, а renderButton делает
 *   trim(null) - deprecated на PHP 8.1+ роняет рендер формы; нормализуем.
 * Используется вместо MarkdownEditor во всех наших формах (ActiveField::text
 * и прямые вызовы) - при обновлении vendor-пакета можно будет пересмотреть.
 */
class MarkdownEditorFix extends MarkdownEditor
{
	/**
	 * BS5+ обслуживаем той же веткой рендера, что и BS4
	 */
	public function isBs4()
	{
		return $this->getBsVer() >= 4;
	}

	protected function renderButton($btn, $options = [], $markup = true)
	{
		$options['icon'] = $options['icon'] ?? '';
		$options['label'] = $options['label'] ?? '';
		if (!empty($options['items']) && $this->getBsVer() >= 5) {
			$options['data-bs-toggle'] = 'dropdown';
		}
		return parent::renderButton($btn, $options, $markup);
	}

	protected function setDefaultHeader()
	{
		if (!empty($this->toolbar)) return;
		parent::setDefaultHeader();
		foreach ($this->toolbar as &$group) {
			if (strpos($group['options']['class'] ?? '', 'float-right') !== false) {
				$group['options']['class'] .= ' float-end';
			}
		}
		unset($group);
		if ($this->showPreview) {
			$this->toolbar[] = [
				'buttons' => [
					self::BTN_PREVIEW => [
						'icon' => 'search',
						'title' => Yii::t('kvmarkdown', 'Preview formatted text'),
					],
				],
				'options' => ['class' => 'pull-right float-right float-end'],
			];
		}
	}

	protected function setDefaultFooter()
	{
		$custom = !empty($this->footerButtons);
		parent::setDefaultFooter();
		if ($custom) return;
		//preview переехал в верхний тулбар
		foreach ($this->footerButtons as $i => $group) {
			if (isset($group['buttons'][self::BTN_PREVIEW])) {
				unset($this->footerButtons[$i]);
			}
		}
	}

	/**
     * Render the editor footer content
     */
    public function renderFooter()
    {
        $buttons = '';
        $this->setDefaultFooter();
        foreach ($this->footerButtons as $group) {
            if (empty($group['buttons'])) {
                continue;
            }
            $buttons .= $this->renderButtonGroup($group, false);
        }

        $content = strtr($this->footer, [
            '{message}' => $this->footerMessage,
            '{buttons}' => $buttons,
        ]);
        if ($content)
        	echo Html::tag('div', $content, $this->footerOptions);
    }}
