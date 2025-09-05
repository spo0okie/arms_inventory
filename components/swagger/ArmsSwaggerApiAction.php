<?php
namespace app\components\swagger;

use app\helpers\ArrayHelper;
use app\modules\api\controllers\BaseRestController;
use light\swagger\SwaggerApiAction;
use OpenApi\Generator;
use OpenApi\Pipeline;
use OpenApi\Util;
use yii\base\Action;
use yii\helpers\FileHelper;
use ReflectionClass;
use ReflectionMethod;

class ArmsSwaggerApiAction extends SwaggerApiAction
{
	
	/** @var string директория для временных файлов */
	public string $tmpDir = '@runtime/swagger-temp';
	
	/*public function run()
	{
		$dirs = (array)$this->scanDir;
		$files = [];
		foreach ($dirs as $dir) {
			$files = array_merge($files, FileHelper::findFiles($dir, ['only' => ['*.php']]));
		}
		
		// Собираем классы из файлов
		$classes = $this->extractClasses($files);
		
		// Подготавливаем временные файлы с аннотациями от родительских методов
		$tmpFiles = $this->generateInheritedAnnotationStubs($classes);
		
		// Генерация OpenAPI документации
		$openapi = Generator::scan(array_merge($files, $tmpFiles), $this->scanOptions);
		
		\Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
		return $openapi->toArray();
	}*/
	
	/*private function extractClasses(array $files): array
	{
		$classes = [];
		foreach ($files as $file) {
			$tokens = token_get_all(file_get_contents($file));
			$namespace = '';
			$class = '';
			for ($i = 0; $i < count($tokens); $i++) {
				if ($tokens[$i][0] === T_NAMESPACE) {
					$namespace = '';
					for ($j = $i + 1; $j < count($tokens); $j++) {
						if ($tokens[$j] === ';') break;
						if (is_array($tokens[$j])) $namespace .= $tokens[$j][1];
					}
				}
				if ($tokens[$i][0] === T_CLASS) {
					for ($j = $i + 1; $j < count($tokens); $j++) {
						if ($tokens[$j][0] === T_STRING) {
							$class = $tokens[$j][1];
							$fqcn = trim($namespace) ? trim($namespace) . '\\' . $class : $class;
							if (class_exists($fqcn)) {
								$classes[] = $fqcn;
							}
							break;
						}
					}
				}
			}
		}
		return $classes;
	}
	
	private function generateInheritedAnnotationStubs(array $classes): array
	{
		$tmpDir = \Yii::getAlias($this->tmpDir);
		if (!is_dir($tmpDir)) {
			FileHelper::createDirectory($tmpDir);
		}
		
		$stubFiles = [];
		
		foreach ($classes as $class) {
			$rc = new ReflectionClass($class);
			$stubCode = "<?php\n";
			$stubCode .= "namespace {$rc->getNamespaceName()};\n";
			$stubCode .= "class __SwaggerStub_" . $rc->getShortName() . " {\n";
			
			foreach ($rc->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
				if (!str_starts_with($method->name, 'action')) {
					continue;
				}
				
				$doc = $method->getDocComment();
				if ($doc && str_contains($doc, '@OA\\')) {
					continue; // уже аннотирован
				}
				
				// ищем в родителях
				$parent = $rc->getParentClass();
				while ($parent) {
					if ($parent->hasMethod($method->name)) {
						$parentMethod = $parent->getMethod($method->name);
						$docParent = $parentMethod->getDocComment();
						if ($docParent && str_contains($docParent, '@OA\\')) {
							// создаём заглушку с аннотацией
							$stubCode .= $docParent . "\n";
							$stubCode .= "public function " . $method->name . "(){}\n\n";
							break;
						}
					}
					$parent = $parent->getParentClass();
				}
			}
			
			$stubCode .= "}\n";
			
			$file = $tmpDir . DIRECTORY_SEPARATOR . $rc->getShortName() . "_swagger_stub.php";
			file_put_contents($file, $stubCode);
			$stubFiles[] = $file;
		}
		
		return $stubFiles;
	}*/
	protected function getSwagger()
	{
		$exclude = ArrayHelper::getValue($this->scanOptions, 'exclude');
		$pattern = ArrayHelper::getValue($this->scanOptions, 'pattern');
		$sources = Util::finder($this->scanDir, $exclude, $pattern);
		
		$generator = new \OpenApi\Generator($this->scanOptions['logger'] ?? null);
		$generator
			->setVersion($this->scanOptions['version'] ?? null)
			->setAliases($this->scanOptions['aliases'] ?? \OpenApi\Generator::DEFAULT_ALIASES)
			->setNamespaces($this->scanOptions['namespaces'] ?? \OpenApi\Generator::DEFAULT_NAMESPACES)
			->setAnalyser($this->scanOptions['analyser'] ?? null)
			->setConfig($this->scanOptions['config'] ?? []);
		
		// воткнуть свой процессор
		$generator
			->withProcessor(function ($pipeline) {
				// Загружаем методы из предков
				$pipeline->insert(new ExpandControllerActions(), \OpenApi\Processors\BuildPaths::class);
			})
			->withProcessor(function (Pipeline $pipeline) {
				// Добавим построение путей из и имен и методов контроллеров Yii2
				$pipeline->insert(new Yii2RouteProcessor(), \OpenApi\Processors\BuildPaths::class);
			})
			->withProcessor(function (Pipeline $pipeline) {
				// Добавим построение путей из и имен и методов контроллеров Yii2
				$pipeline->insert(new RemoveBaseClasses([
					BaseRestController::class
				]), \OpenApi\Processors\BuildPaths::class);
			})
		;
		
		
		return $generator->generate(
			$sources,
			$this->scanOptions['analysis'] ?? null,
			$this->scanOptions['validate'] ?? true
		);
	}
}
