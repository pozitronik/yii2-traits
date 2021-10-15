<?php
declare(strict_types = 1);

namespace pozitronik\traits\traits;

use yii\db\ActiveQueryTrait as YiiActiveQueryTrait;
use yii\db\ActiveRecord;

/**
 * Обёртка над ActiveQuery с полезными и общеупотребительными функциями
 * http://www.yiiframework.com/doc-2.0/guide-db-active-record.html#customizing-query-classes
 */
trait ActiveQueryTrait {
	use YiiActiveQueryTrait;

	/**
	 * Селектор для флага "deleted", если он присутствует в таблице
	 * @param bool $deleted
	 * @return $this
	 * @example ActiveRecord::find()->active()->all()
	 */
	public function active(bool $deleted = false):self {
		/** @var ActiveRecord $class */
		$class = new $this->modelClass();//Хак для определения вызывающего трейт класса (для определения имени связанной таблицы)
		$tableName = $class::tableName();
		return $class->hasAttribute('deleted')?$this->andOnCondition([$tableName.'.deleted' => $deleted]):$this;
	}

}