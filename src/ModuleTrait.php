<?php
declare(strict_types = 1);

namespace pozitronik\traits;

use pozitronik\helpers\ArrayHelper;
use pozitronik\helpers\ModuleHelper;
use pozitronik\helpers\Utils;
use Throwable;
use Yii;
use yii\base\InvalidConfigException;
use yii\base\Module;
use yii\helpers\Html;
use yii\helpers\Url;

/**
 * Trait ModuleExtended
 *
 * @property-read string $namespace
 * @property-read string $alias
 */
trait ModuleTrait {
	protected $_namespace;
	protected $_alias;

	/**
	 * Возвращает неймспейс загруженного модуля (для вычисления алиасных путей внутри модуля)
	 * @return string
	 */
	public function getNamespace():string {
		if (null === $this->_namespace) {
			$class = get_class($this);
			if (false !== ($pos = strrpos($class, '\\'))) {
				$this->_namespace = substr($class, 0, $pos);
			}
		}
		return $this->_namespace;
	}

	/**
	 * Возвращает зарегистрированный алиас модуля
	 * @return string
	 */
	public function getAlias():string {
		if (null === $this->_alias) {
			/*Регистрируем алиас плагина*/
			/** @var Module|ModuleTrait $this */
			$this->_alias = "@{$this->id}";
			/** @var Module|ModuleTrait $this */
			Yii::setAlias($this->_alias, $this->basePath);
		}

		return $this->_alias;
	}

	/**
	 * Функция генерирует пункт меню навигации внутри модуля
	 * @param string $label
	 * @param string|array $uroute
	 * @return array
	 * @throws InvalidConfigException
	 * @throws Throwable
	 */
	public static function breadcrumbItem(string $label, $uroute = ''):array {
		if ((null === $module = static::getInstance()) && null === $module = ModuleHelper::GetModuleByClassName(static::class)) {
			$module = Yii::$app->controller->module;
		}
		/** @var self $module */
		return ['label' => $label, 'url' => $module::to($uroute)];
	}

	/**
	 * Возвращает путь внутри модуля. Путь всегда будет абсолютный, от корня
	 * @param string|array $route -- контроллер и экшен + параметры
	 * @return string
	 * @throws InvalidConfigException
	 * @throws Throwable
	 * @example SalaryModule::to(['salary/index','id' => 10]) => /salary/salary/index?id=10
	 * @example UsersModule::to('users/index') => /users/users/index
	 */
	public static function to($route = ''):string {
		if ((null === $module = static::getInstance()) && null === $module = ModuleHelper::GetModuleByClassName(static::class)) {
			throw new InvalidConfigException("Модуль ".static::class." не подключён");
		}
		if (is_array($route)) {/* ['controller{/action}', 'actionParam' => $paramValue */
			ArrayHelper::setValue($route, 0, Utils::setAbsoluteUrl($module->id.Utils::setAbsoluteUrl(ArrayHelper::getValue($route, 0))));
		} else {/* 'controller{/action}' */
			if ('' === $route) $route = $module->defaultRoute;
			$route = Utils::setAbsoluteUrl($module->id.Utils::setAbsoluteUrl($route));
		}
		return Url::to($route);
	}

	/**
	 * Генерация html-ссылки внутри модуля (аналог Html::a(), но с автоматическим учётом путей модуля).
	 * @param string $text
	 * @param array|string|null $url
	 * @param array $options
	 * @return string
	 * @throws InvalidConfigException
	 * @throws Throwable
	 */
	public static function a(string $text, $url = null, array $options = []):string {
		$url = static::to($url);
		return Html::a($text, $url, $options);
	}

	/**
	 * @return null|static
	 * @see Module::getInstance()
	 * @noinspection ReturnTypeCanBeDeclaredInspection
	 */
	abstract public static function getInstance();

}