<?php
declare(strict_types = 1);

namespace pozitronik\traits;

use pozitronik\helpers\ArrayHelper;
use pozitronik\helpers\ReflectionHelper;
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
		$controllerId = static::ExtractControllerId(static::class);
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
	public static function to(string $action, array $params = [], $scheme = false):string {
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
		$names = ArrayHelper::getColumn(ReflectionHelper::GetMethods(static::class), 'name');
		$names = preg_filter('/^action([A-Z])(\w+?)/', '$1$2', $names);
		if ($asRequestName) {
			foreach ($names as &$name) $name = self::GetActionRequestName($name);
		}
		return $names;
	}

	/**
	 * Переводит вид имени экшена к виду запроса, который этот экшен дёргает.
	 * @param string $action
	 * @return string
	 * @example actionSomeActionName => some-action-name
	 * @example OtherActionName => other-action-name
	 */
	public static function GetActionRequestName(string $action):string {
		/** @var array $lines */
		$lines = preg_split('/(?=[A-Z])/', $action, -1, PREG_SPLIT_NO_EMPTY);
		if ('action' === $lines[0]) unset($lines[0]);
		return mb_strtolower(implode('-', $lines));
	}

	/**
	 * Вытаскивает из имени класса контроллера его id
	 * app/shit/BlaBlaBlaController => bla-bla-bla
	 * @param string $className
	 * @return string
	 */
	private static function ExtractControllerId(string $className):string {
		$controllerName = preg_replace('/(^.+)(\\\)([A-Z].+)(Controller$)/', '$3', $className);//app/shit/BlaBlaBlaController => BlaBlaBla
		return mb_strtolower(implode('-', preg_split('/([[:upper:]][[:lower:]]+)/', $controllerName, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY)));
	}

}