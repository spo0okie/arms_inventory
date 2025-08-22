<?php


namespace app\components;


use app\helpers\StringHelper;
use app\models\ArmsModel;
use Yii;
use yii\base\InvalidConfigException;
use yii\base\UnknownPropertyException;
use yii\bootstrap5\Tabs;
use yii\helpers\Html;
use yii\helpers\Url;

class TabsWidget extends Tabs
{
	public $cookieName='nonameTabs';
	public $defaultItem='tab1';
	public $encodeLabels=false;
	public const badgeStart='<span class="badge rounded-pill p-1 m-1 bg-secondary opacity-25">';
	public const badgeEnd='</span>';
	
	
	public static function attrTabLabel($model,$attr,$view)
	{
		[$title,$options]=ModelFieldWidget::fieldTitle($model,$attr,$view);
		$label=Html::tag('span',$title,$options);
		if (is_array($model->$attr)) {
			$label.=TabsWidget::badgeStart.count($model->$attr).TabsWidget::badgeEnd;
		}
		return $label;
	}
	
	public function prepareItems(array &$items, string $prefix = ''): void
	{
		//что в куках записано про открытую вкладку (или берем по умолчанию)
		$cookieTab=$_COOKIE[$this->cookieName]??$this->defaultItem;
		
		//если имя вкладки передано через URL, то делаем оверрайд сохраненного в куках значения
		if (($hrefTab= Yii::$app->request->get('tab','unset'))!='unset') {
			$cookieTab=$hrefTab;
		}
		
		$counter=0;
		$first=null;
		//$gotActive=false;
		foreach ($items as $i=>$tab) {
			if (is_null($first)) $first=$i;	//запоминаем какая была первой
			
			$counter++;
			
			// определяем ID вкладки
			$tabId='tab_'.$counter;
			if (isset($items[$i]['id'])) {
				$tabId=$items[$i]['id'];
				unset($items[$i]['id']);
			}
			
			if ($cookieTab==$tabId) {
				//$gotActive=true;
				$items[$i]['active']=true;
			}
			
			//$items[$i]['options']['id']='tab-'.$tabId.'-content';
			
			//if (!isset($this->tabs[$i]['label'])) $this->tabs[$i]['label']='Tab '.$counter;
			$items[$i]['headerOptions']=['onClick'=>'document.cookie = "'.$this->cookieName.'='.$tabId.'"','id'=>'tab-'.$tabId];
		}
		
		parent::prepareItems($items, $prefix);
	}
	
	
	public static function addWikiLinks(&$tabs,$links) {
		$tabNumber=0;
		$defaultNamesCount=0;
		$wikiLinks= WikiPageWidget::getLinks($links);
		foreach ($wikiLinks as $name=>$url) {
			//идентификатор вкладки
			$tabId='wiki'.$tabNumber;
			
			//если по какой-то причине имя не распозналось и не было префикса и вернулся просто URL
			if ($name==$url) {
				//имя по умолчанию для вкладки (Wiki, Wiki #2, Wiki #3 ...)
				$name='Wiki';
				if ($defaultNamesCount++) $name.=' #'.($defaultNamesCount+1);
			}
			
			$editLink=Html::tag('i','',[
				'class'=>"fas fa-pencil-alt ps-1",
				'onClick'=>'window.open("'.$url.'?do=edit'.'","_blank");',
			]);
			
			$tabs[]=[
				'label'=>$name.$editLink,
				'id'=>$tabId,
				'content' => WikiPageWidget::Widget(['list'=>$links,'item'=>$name]),
			];
			
			$tabNumber++;
		}
	}
	
	
	/**
	 * Формирует content вкладки для асинхронной подгрузки в нее данных.
	 * Если в заголовке вкладки будет span с классом count, а в ответе сервера будет X-Pagination-Total-Count,
	 * то заполнит это поле количеством элементов, которые вернулись в ответе.
	 * @param string $tab ID вкладки
	 * @param string $url URL для AJAX запроса, который вернет содержимое вкладки
	 * @return string
	 */
	public static function ajaxLoadItems($tab,$url) {
		if (is_array($url)) $url= Url::to($url);
		return <<<HTML
			<div id="{$tab}Content">
				<div class="spinner-border" role="status">
					<span class="visually-hidden">Loading...</span>
				</div>
			</div>
			<script>
				$(document).ready(function() {
					$.ajax({
						url: "{$url}",
						method: "GET",
						success: function(data, textStatus, jqXHR) {
							const totalCount = jqXHR.getResponseHeader("X-Pagination-Total-Count");
				
							// Обновляем содержимое
							jQuery("#{$tab}Content").hide().html(data);
							setTimeout(function () {
								jQuery("#{$tab}Content").fadeToggle();
								ExpandableCardInitAll();
							}, 500);

                            //ищем
							const \$li = $("li#tab-{$tab}");
				            const \$countSpan = \$li.find("a span.count");
                            
                            if (totalCount !== null && \$countSpan.length) {
                                const bg= totalCount === "0" ? "bg-warning" : "bg-warning";
                				\$countSpan.html(`
                    				<span class="badge rounded-pill \${bg} px-1 mx-1">\${totalCount}</span>
                				`);
            				}

							// Затемняем вкладку, если строк 0
							if (totalCount === "0") {
								\$li.addClass("muted-tab"); // Пример класса
							} else {
								\$li.removeClass("muted-tab");
							}
						}
					});
				});
			</script>
HTML;
	}
	
	/**
	 * Возвращает элемент вкладки для асинхронной подгрузки таблицы.
	 * @param string       $id ID вкладки
	 * @param string       $gridId ID DynagridWidget, который будет на подгруженном URL
	 * @param string       $label Название вкладки
	 * @param string|array $url URL для подгрузки
	 * @param string       $staticContent статичный контент перед подгружаемым
	 * @return array
	 * @throws InvalidConfigException
	 */
	public static function asyncDynagridTab(string $id, string $gridId, string $label, string|array $url, string $staticContent=''): array
	{
		$item=[
			'id'=>$id,
			'label'=>"$label"
				.'<span class="count"></span>'
				.'<i title="настройки таблицы" data-bs-toggle="modal" data-bs-target="#'.$gridId.'-grid-modal" class="small fas fa-wrench fa-fw"></i>',
			'content'=>$staticContent
				.TabsWidget::ajaxLoadItems($id,$url)
		
		];
		//поскольку мы асинхронно подгрузим таблицу, она может сохранять свои настройки
		//причем обработчик сохранения в той странице которая подгружается асинхронно, а не в этой
		//поэтому добавляем обработчик сохранения настроек в эту страницу
		DynaGridWidget::handleSave($gridId);
		return $item;
	}
	
	
	public static function asyncDynagridPropertyTab(
		ArmsModel $model,
		string $property,
		bool|null $showArchived=null,
		array $filter=[],
		string $linkClass='',
		$label='',
		$staticContent=''
	): array
	{
		$classId=StringHelper::class2Id($model::class);
		$propertyId=StringHelper::class2Id($property);
		
		//если мы не сказали какой класс мы подгружаем в Dynagrid для нашего property - выясняем через linksSchema
		if (!$linkClass) {
			$propertyLink=$model->attributeIsLoader($property);
			if (!$propertyLink) throw new UnknownPropertyException("Property '$property' is not a link in model ".get_class($model));
			$linkClass=StringHelper::class2Id($model->attributeLinkClass($propertyLink));
		}
		
		//если мы не заполнили фильтр, как выбрать только связанные с данным объектом элементы, то генерируем фильтр на основании linksSchema
		if (!count($filter)) {
			$propertyLink=$model->attributeIsLoader($property);
			if (!$propertyLink) throw new UnknownPropertyException("Property '$property' is not a link in model ".get_class($model));
			$reverseLink=$model->attributeReverseLink($propertyLink);
			if (!$reverseLink) throw new UnknownPropertyException("Reverse link of property '$propertyLink' is undefined in model ".get_class($model));
			if (StringHelper::endsWith($reverseLink,'_ids'))
				$filter=[$reverseLink=>[$model->id]];
			else
				$filter=[$reverseLink=>$model->id];
		}
		
		$tabId=$classId.'-'.$propertyId;
		$gridId=$classId.'-'.$propertyId.'-list';
		if (!$label) $label=$model->getAttributeLabel($property);
		$url=[
			$linkClass.'/async-grid',
			'SearchOverride'=>$filter,
			'source'=> Url::currentNonRecursive(),
			'gridId'=>$gridId,
		];
		if (!is_null($showArchived))
			$url['showArchived']=$showArchived;
		
		return static::asyncDynagridTab($tabId,	$gridId, $label, $url, $staticContent);
	}
}