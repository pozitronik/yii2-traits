<?php
declare(strict_types = 1);

namespace pozitronik\traits;

use pozitronik\helpers\DateHelper;
use yii\db\ActiveQuery;
use Yii;
use pozitronik\helpers\ArrayHelper;
use Throwable;
use yii\db\ActiveQueryTrait as YiiActiveQueryTrait;
use yii\db\ActiveRecord;
use yii\db\Command;
use yii\db\Connection;
use yii\db\ExpressionInterface;

/**
 * Обёртка над ActiveQuery с полезными и общеупотребительными функциями
 * http://www.yiiframework.com/doc-2.0/guide-db-active-record.html#customizing-query-classes
 */
trait ActiveQueryTrait {
	use YiiActiveQueryTrait;

	/**
	 * Глобальная замена findWorkOnly
	 * Возвращает записи, не помеченные, как удалённые
	 * @param bool $deleted
	 * @return $this
	 */
	public function active(bool $deleted = false):self {
		/** @var ActiveRecord $class */
		$class = new $this->modelClass();//Хак для определения вызывающего трейт класса (для определения имени связанной таблицы)
		$tableName = $class::tableName();
		return $class->hasAttribute('deleted')?$this->andOnCondition([$tableName.'.deleted' => $deleted]):$this;
	}

	/**
	 * В некоторых поисковых моделях часто используется такое условие: если в POST передана дата, то искать все записи за неё, иначе игнорировать
	 * @param string|array $field
	 * @param string|null $value
	 * @param bool $formatted_already - true: принять дату как уже форматированную в Y-m-d (для тех случаев, где Женька сделал так)
	 * @return ActiveQuery|self
	 * @throws Throwable
	 */
	public function andFilterDateBetween($field, ?string $value, bool $formatted_already = false):ActiveQuery {
		if (null === $value) return $this;

		$date = explode(' ', $value);
		$start = ArrayHelper::getValue($date, 0);
		$stop = ArrayHelper::getValue($date, 2);//$date[1] is delimiter

		if (DateHelper::isValidDate($start, $formatted_already?'Y-m-d':'d.m.Y') && DateHelper::isValidDate($stop, $formatted_already?'Y-m-d':'d.m.Y')) {/*Проверяем даты на валидность*/
			if (is_array($field)) {
				return $this->andFilterWhere([
					$field[0] => self::extractDate($start, $formatted_already),
					$field[1] => self::extractDate($stop, $formatted_already)
				]);
			}

			return $this->andFilterWhere([
				'between', $field, self::extractDate($start, $formatted_already).' 00:00:00',
				self::extractDate($stop, $formatted_already).' 23:59:00'
			]);
		}

		return $this;
	}

	/**
	 * @param string $date_string
	 * @param bool $formatted_already
	 * @return string
	 */
	private static function extractDate(string $date_string, bool $formatted_already):string {
		return $formatted_already?$date_string:date('Y-m-d', strtotime($date_string));
	}

	/**
	 * Держим долго считаемый count для запроса в кеше
	 * @param int $duration
	 * @return int
	 */
	public function countFromCache(int $duration = DateHelper::SECONDS_IN_HOUR):int {
		$countQuery = clone $this;
		$countQuery->distinct()
			->limit(false)
			->offset(false);//нелимитированный запрос для использования его в качестве ключа
		return Yii::$app->cache->getOrSet($this->createCommand()->rawSql, static function() use ($countQuery) {
			return (int)$countQuery->count();
		}, $duration);
	}

	/**
	 * @param string $name
	 * @param bool $checkVars
	 * @param bool $checkBehaviors
	 * @return bool
	 * @see Model::hasProperty()
	 * @noinspection ReturnTypeCanBeDeclaredInspection
	 * @noinspection PhpMissingParamTypeInspection
	 */
	abstract public function hasProperty($name, $checkVars = true, $checkBehaviors = true);

	/**
	 * @param string|array $condition
	 * @param array $params
	 * @return self
	 * @see ActiveQuery::andOnCondition()
	 * @noinspection ReturnTypeCanBeDeclaredInspection
	 * @noinspection PhpMissingParamTypeInspection
	 */
	abstract public function andOnCondition($condition, $params = []);

	/**
	 * @param bool $value
	 * @return self
	 * @see ActiveQuery::distinct()
	 * @noinspection ReturnTypeCanBeDeclaredInspection
	 * @noinspection PhpMissingParamTypeInspection
	 */
	abstract public function distinct($value = true);

	/**
	 * @param Connection|null $db
	 * @return Command
	 * @see ActiveQuery::createCommand()
	 * @noinspection ReturnTypeCanBeDeclaredInspection
	 * @noinspection PhpMissingParamTypeInspection
	 */
	abstract public function createCommand($db = null);

	/**
	 * @param string $q
	 * @param Connection|null $db
	 * @return int|string
	 * @see ActiveQuery::count()
	 * @noinspection PhpMissingParamTypeInspection
	 */
	abstract public function count($q = '*', $db = null);

	/**
	 * @param int|ExpressionInterface|null $limit
	 * @return self
	 * @see ActiveQuery::limit()
	 * @noinspection ReturnTypeCanBeDeclaredInspection
	 */
	abstract public function limit($limit);

	/**
	 * @param int|ExpressionInterface|null $offset
	 * @return self
	 * @see ActiveQuery::offset()
	 * @noinspection ReturnTypeCanBeDeclaredInspection
	 */
	abstract public function offset($offset);

	/**
	 * @param array $array
	 * @return mixed
	 * @see ActiveQuery::andFilterWhere()
	 */
	abstract public function andFilterWhere(array $array);

}