<?php
namespace app\components\widgets\page;

use app\models\ArmsModel;
use yii\base\Widget;

/**
 * Виджет рендера View файла модели
 * Если у модели нет своего view файла, то пытается найти общий в layouts
 */

class ModelWidget extends Widget
{
	/** @var ?ArmsModel $model Модель которую надо отрендерить */
	public ?ArmsModel $model;
	
	public string $view='item';				//какую ее форму отрендерить (по умолчанию item)
	public array $options=[];				//опции рендера
	public string $empty='- отсутствует -';	//что вывести если модель пустая
	

	private string $modelViewsPath;			//где у модели сложены view-файлы
	private string $modelViewPath;			//путь к конкретному view-файлу

	public function init(): void {
		if (is_null($this->model)) return;	//если у нас нет модели, значит мы просто вернем emtpy
											//нечего инициализировать

		//загружаем пути к view файлам
		$this->modelViewsPath=$this->model->viewsPath;
		
		//формируем конечный путь
		$this->modelViewPath=$this->modelViewsPath.'/'.$this->view;
		
		//проверяем конечный путь
		if (!is_file(str_replace('@app',$_SERVER['DOCUMENT_ROOT'],$this->modelViewPath.'.php'))) {
			
			//в случае неудачи пытаемся сделать fallback на общие рендер файлы
			$this->modelViewPath='@app/views/layouts/'.$this->view.'.php';
			
			//проверяем возможность fallback
			if (!is_file(str_replace('@app',$_SERVER['DOCUMENT_ROOT'],$this->modelViewPath.'.php'))) {
				
				//ничего не вышло - паника
				$class=get_class($this->model);
				throw new \Exception("Model $class view file not found: {$this->view}");
			}
		}
		
		$this->options['model']=$this->model;
	}

	public function run()
	{
		if (is_null($this->model)) return $this->empty;
		return $this->render($this->modelViewPath, $this->options);
	}
}