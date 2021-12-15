<?php
declare(strict_types = 1);

namespace pozitronik\traits\traits;

use pozitronik\helpers\ControllerHelper;
use pozitronik\helpers\Utils;
use ReflectionException;
use yii\base\UnknownClassException;
use yii\helpers\Url;

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
	 * По имени экшена возвращает строковой абсолютный Url в приложении
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