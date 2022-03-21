<?php
declare(strict_types = 1);

namespace pozitronik\traits\traits;

use pozitronik\helpers\ControllerHelper;
use pozitronik\helpers\Utils;
use ReflectionException;
use yii\base\UnknownClassException;
use yii\helpers\Url;
use yii\web\Controller;

/**
 * Трейт для аугментации контроллеров
 */
trait ControllerTrait {

	/**
	 * По имени экшена возвращает абсолютный Url в приложении
	 * @param string $action
	 * @param array $params
	 * @return array
	 * @example SomeController::GetActionUrl('index', ['id' => 1]) => ['/some/index', 'id' => 1]
	 */
	public static function GetActionUrl(string $action, array $params = []):array {
		$controllerId = ControllerHelper::ExtractControllerId(static::class);
		$route = Utils::setAbsoluteUrl($controllerId.Utils::setAbsoluteUrl($action));
		if ([] !== $params) {
			array_unshift($params, $route);
		} else {
			$params = [$route];
		}
		return $params;
	}

	/**
	 * По имени экшена возвращает строковой абсолютный Url в приложении.
	 * Не учитывает, находится ли контроллер в модуле (использовать Module::to() или $controller->link())
	 * @param string $action
	 * @param array $params
	 * @param bool|string $scheme @see Url::to() $scheme parameter
	 * @return string
	 * @example SomeController::to('index', ['id' => 1]) => '/some/index?id=1'
	 */
	public static function to(string $action, array $params = [], bool|string $scheme = false):string {
		return Url::to(self::GetActionUrl($action, $params), $scheme);
	}

	/**
	 * Альтернативный способ получения ссылки в созданном экземпляре контроллера.
	 * Метод учитывает модуль контроллера (если тот импортирует ModuleTrait).
	 * @param string $action
	 * @param array $params
	 * @param bool|string $scheme
	 * @return string
	 */
	public function link(string $action, array $params = [], bool|string $scheme = false):string {
		/** @var Controller $this */
		return (method_exists($this->module, 'to'))
			?$this->module::to(self::GetActionUrl($action, $params))
			:Url::to(self::GetActionUrl($action, $params), $scheme);
	}

	/**
	 * Возвращает все экшены контроллера
	 * @param bool $asRequestName Привести имя экшена к виду в запросе
	 * @return string[]
	 * @throws ReflectionException
	 * @throws UnknownClassException
	 */
	public static function GetControllerActions(bool $asRequestName = true):array {
		return ControllerHelper::GetControllerActions(static::class, $asRequestName);
	}

}