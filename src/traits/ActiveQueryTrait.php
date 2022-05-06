<?php
declare(strict_types = 1);

namespace pozitronik\traits\traits;

use yii\base\NotSupportedException;
use yii\db\ActiveQueryInterface;
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

	/**
	 * Выборка по условию, включающая релейшен только тогда, когда выборка активна.
	 * Нужна для оптимизации поиска по hasMany-связям, для того, чтобы не использовать distinct() без нужды.
	 * @param array $condition
	 * @param string|array $relation
	 * @param bool $eagerLoading
	 * @param string $joinType
	 * @param bool $distinct
	 * @return ActiveQueryInterface
	 * @throws NotSupportedException
	 */
	public function andFilterWhereRelation(array $condition, string|array $relation, bool $eagerLoading = true, string $joinType = 'LEFT JOIN', bool $distinct = true):ActiveQueryInterface {
		/** @var ActiveQueryInterface $this */
		if ([] !== $condition = $this->filterCondition($condition)) {
			$this->joinWith($relation, $eagerLoading, $joinType);
			$this->andWhere($condition);
			if ($distinct) $this->distinct();
		}
		return $this;
	}

	/**
	 * inheritDoc
	 */
	public function isEmpty($value):bool {
		return '' === $value || $value === [] || null === $value || (is_string($value) && '' === trim($value));
	}

}