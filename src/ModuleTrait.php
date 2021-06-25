<?php
declare(strict_types = 1);

namespace pozitronik\traits;

use pozitronik\helpers\ModuleHelper;
use Throwable;
use Yii;
use yii\base\InvalidConfigException;
use yii\base\Module;

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
		if ((null === $module = Module::getInstance()) && null === $module = ModuleHelper::GetModuleByClassName(static::class)) {
			$module = Yii::$app->controller->module;
		}
		/** @var self $module */
		return ['label' => $label, 'url' => $module::to($uroute)];
	}

}