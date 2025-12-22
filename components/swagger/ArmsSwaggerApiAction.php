<?php
namespace app\components\swagger;

use app\helpers\ArrayHelper;
use app\modules\api\controllers\BaseRestController;
use light\swagger\SwaggerApiAction;
use OpenApi\Generator;
use OpenApi\Pipeline;
use OpenApi\Processors\BuildPaths;
use OpenApi\Util;


class ArmsSwaggerApiAction extends SwaggerApiAction
{
	
	/** @var string директория для временных файлов */
	public string $tmpDir = '@runtime/swagger-temp';
	

	protected function getSwagger()
	{
		$exclude = ArrayHelper::getValue($this->scanOptions, 'exclude');
		$pattern = ArrayHelper::getValue($this->scanOptions, 'pattern');
		$sources = Util::finder($this->scanDir, $exclude, $pattern);
		
		$generator = new Generator($this->scanOptions['logger'] ?? null);
		$generator
			->setVersion($this->scanOptions['version'] ?? null)
			->setAliases($this->scanOptions['aliases'] ?? Generator::DEFAULT_ALIASES)
			->setNamespaces($this->scanOptions['namespaces'] ?? Generator::DEFAULT_NAMESPACES)
			->setAnalyser($this->scanOptions['analyser'] ?? null)
			->setConfig($this->scanOptions['config'] ?? []);
		
		// воткнуть свой процессор
		$generator
			->withProcessor(function ($pipeline) {
				// Загружаем методы из предков
				$pipeline->insert(new ExpandControllerActions(), BuildPaths::class);
			})
			
			->withProcessor(function (Pipeline $pipeline) {
				// Добавим построение путей из и имен и методов контроллеров Yii2
				$pipeline->insert(new ExpandMacrosProcessor(), BuildPaths::class);
			})
			->withProcessor(function (Pipeline $pipeline) {
				// Добавим построение путей из и имен и методов контроллеров Yii2
				$pipeline->insert(new RemoveBaseClasses([
					BaseRestController::class
				]), BuildPaths::class);
			})
			->withProcessor(function (Pipeline $pipeline) {
				// Добавим построение схем моделей
				$pipeline->insert(new GenerateModelSchemaProcessor(), BuildPaths::class);
			})
			->withProcessor(function (Pipeline $pipeline) {
				// Добавим стандартные коды ответов на ошибки
				$pipeline->insert(new AddStandardErrorResponsesProcessor(), BuildPaths::class);
			})
		;
		
		
		return $generator->generate(
			$sources,
			$this->scanOptions['analysis'] ?? null,
			$this->scanOptions['validate'] ?? true
		);
	}
}
