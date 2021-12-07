<?php
declare(strict_types = 1);

namespace pozitronik\traits\traits;

use pozitronik\helpers\ArrayHelper;
use pozitronik\traits\models\ActiveQuery;
use RuntimeException;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\db\ActiveRecord;
use Throwable;
use yii\db\Exception as DbException;
use yii\db\Transaction;
use yii\helpers\VarDumper;

/**
 * Trait ARExtended
 * Расширения модели ActiveRecord
 */
trait ActiveRecordTrait {

	/**
	 * @return ActiveQuery
	 */
	public static function find():ActiveQuery {
		return new ActiveQuery(static::class);
	}

	/**
	 * Обёртка для быстрого поиска моделей с опциональным выбросом логируемого исключения
	 * Упрощает проверку поиска моделей
	 * @param mixed $id Поисковое условие (предпочтительно primaryKey, но не ограничиваемся им)
	 * @param null|Throwable $throw - Если передано исключение, оно выбросится в случае ненахождения модели
	 * @return null|static
	 * @throws Throwable
	 * @example Users::findModel($id, new NotFoundException('Пользователь не найден'))
	 *
	 * @example if (null !== $user = Users::findModel($id)) return $user
	 */
	public static function findModel($id, ?Throwable $throw = null):?static {
		if (null !== ($model = static::findOne($id))) return $model;
		if (null !== $throw) {
			throw $throw;
		}
		return null;
	}

	/**
	 * Ищет по указанному условию, возвращая указанный атрибут модели или $default, если модель не найдена
	 * @param mixed $condition Поисковое условие
	 * @param string|null $attribute Возвращаемый атрибут (если не задан, то вернётся первичный ключ)
	 * @param null|mixed $default
	 * @return mixed
	 * @throws InvalidConfigException
	 */
	public static function findModelAttribute($condition, ?string $attribute = null, $default = null) {
		if (null === $model = static::findOne($condition)) return $default;

		if (null === $attribute) {
			$primaryKeys = static::primaryKey();
			if (!isset($primaryKeys[0])) throw new InvalidConfigException('"'.static::class.'" must have a primary key.');

			$attribute = $primaryKeys[0];
		}
		return $model->$attribute;
	}

	/**
	 * Получение имени первичного ключа в лоб. Для составных ключей работать не будет. Нужно для тупой оптимизации SelectModelWidget, а может и не нужно и надо будет переписать
	 * @return string|null
	 */
	public static function pkName():?string {
		$primaryKeys = static::primaryKey();
		return $primaryKeys[0]??null;
	}

	/**
	 * По итерируемому списку ключей вернёт список подходящих моделей
	 * @param null|int[] $keys Итерируемый список ключей
	 * @return self[]
	 * @throws Throwable
	 */
	public static function findModels(?array $keys):array {
		if (null === $keys) return [];
		$result = [];
		foreach ($keys as $key) {
			if (null !== $model = static::findModel($key)) $result[] = $model;
		}
		return $result;
	}

	/**
	 * Возвращает существующую запись в ActiveRecord-модели, найденную по условию, если же такой записи нет - возвращает новую модель
	 * @param array|string $searchCondition
	 * @return ActiveRecord|self
	 */
	public static function getInstance($searchCondition):self {
		$instance = static::find()->where($searchCondition)->one();
		return $instance??new static();
	}

	/**
	 * Первый параметр пока что специально принудительно указываю массивом, это позволяет не накосячить при задании параметров. Потом возможно будет убрать
	 * !Функция была отрефакторена и после этого не тестировалась!
	 * @param array $searchCondition
	 * @param null|array $fields
	 * @param bool $ignoreEmptyCondition Игнорировать пустое поисковое значение
	 * @param bool $forceUpdate Если запись по условию найдена, пытаться обновить её
	 * @param bool $throwOnError
	 * @return ActiveRecord|self|null
	 * @throws Exception
	 * @throws InvalidConfigException
	 */
	public static function addInstance(array $searchCondition, ?array $fields = null, bool $ignoreEmptyCondition = true, bool $forceUpdate = false, bool $throwOnError = true):?self {
		if ($ignoreEmptyCondition && (empty($searchCondition) || (is_array($searchCondition) && empty(reset($searchCondition))))) return null;

		$instance = static::getInstance($searchCondition);
		if ($instance->isNewRecord || $forceUpdate) {
			$instance->loadArray($fields??$searchCondition);
			if (!$instance->save() && $throwOnError) {
				throw new Exception("{$instance->formName()} errors: ".VarDumper::dumpAsString($instance->errors));
			}
		}
		return $instance;
	}

	/**
	 * Обратный аналог oldAttributes: после изменения AR возвращает массив только изменённых атрибутов
	 * @param array $changedAttributes Массив старых изменённых аттрибутов
	 * @return array
	 */
	public function newAttributes(array $changedAttributes):array {
		/** @var ActiveRecord $this */
		$newAttributes = [];
		$currentAttributes = $this->attributes;
		foreach ($changedAttributes as $item => $value) {
			if ($currentAttributes[$item] !== $value) $newAttributes[$item] = $currentAttributes[$item];
		}
		return $newAttributes;
	}

	/**
	 * Фикс для changedAttributes, который неправильно отдаёт список изменённых аттрибутов (туда включаются аттрибуты, по факту не менявшиеся).
	 * @param array $changedAttributes
	 * @return array
	 */
	public function changedAttributes(array $changedAttributes):array {
		/** @var ActiveRecord $this */
		$updatedAttributes = [];
		$currentAttributes = $this->attributes;
		foreach ($changedAttributes as $item => $value) {
			if ($currentAttributes[$item] !== $value) $updatedAttributes[$item] = $value;
		}
		return $updatedAttributes;
	}

	/**
	 * Вычисляет разницу между старыми и новыми аттрибутами
	 * @return array
	 * @throws Throwable
	 */
	public function identifyChangedAttributes():array {
		$changedAttributes = [];
		/** @var ActiveRecord $this */
		foreach ($this->attributes as $name => $value) {
			/** @noinspection TypeUnsafeComparisonInspection */
			if (ArrayHelper::getValue($this, "oldAttributes.$name") != $value) $changedAttributes[$name] = $value;//Нельзя использовать строгое сравнение из-за преобразований БД
		}
		return $changedAttributes;
	}

	/**
	 * Работает аналогично saveAttribute, но сразу сохраняет данные
	 * Отличается от updateAttribute тем, что триггерит onAfterSave
	 * @param string $name
	 * @param mixed $value
	 */
	public function setAndSaveAttribute(string $name, $value):void {
		/** @var ActiveRecord $this */
		$this->setAttribute($name, $value);
		$this->save();
	}

	/**
	 * Работает аналогично saveAttributes, но сразу сохраняет данные
	 * Отличается от updateAttributes тем, что триггерит onAfterSave
	 * @param null|array $values
	 */
	public function setAndSaveAttributes(?array $values):void {
		/** @var ActiveRecord $this */
		$this->setAttributes($values, false);
		$this->save();
	}

	/**
	 * Универсальная функция удаления любой модели
	 */
	public function safeDelete():void {
		/** @var ActiveRecord $this */
		if ($this->hasAttribute('deleted')) {
			/** @noinspection PhpUndefinedFieldInspection */
			$this->setAndSaveAttribute('deleted', !$this->deleted);
			$this->afterDelete();
		} else {
			$this->delete();
		}
	}

	/**
	 * Грузим объект из массива без учёта формы
	 * @param null|array $arrayData
	 * @return bool
	 */
	public function loadArray(?array $arrayData):bool {
		/** @var ActiveRecord $this */
		return $this->load($arrayData, '');
	}

	/**
	 * @param string $property
	 * @return string
	 */
	public function asJSON(string $property):string {
		/** @var ActiveRecord $this */
		if (!$this->hasAttribute($property)) throw new RuntimeException("Field $property not exists in the table ".$this::tableName());
		return json_encode($this->$property, JSON_PRETTY_PRINT + JSON_UNESCAPED_UNICODE);
	}

	/**
	 * Удаляет набор моделей по набору первичных ключей
	 * @param array $primaryKeys
	 * @throws Throwable
	 */
	public static function deleteByKeys(array $primaryKeys):void {
		foreach ($primaryKeys as $primaryKey) {
			if (null !== $model = self::findModel($primaryKey)) {
				$model->delete();
			}
		}
	}

	/**
	 * Отличия от базового deleteAll(): работаем в цикле для корректного логирования (через декомпозицию)
	 * @param null|mixed $condition
	 * @param bool $transactional
	 * @return int|null
	 * @throws DbException
	 * @throws Throwable
	 */
	public static function deleteAllEx($condition = null, bool $transactional = true):?int {
		$self_class_name = static::class;
		$self_class = new $self_class_name();
		/** @var ActiveRecord $self_class */
		$deletedModels = $self_class::findAll($condition);
		$dc = 0;
		/** @var Transaction $transaction */
		if ($transactional && null === $transaction = static::getDb()->beginTransaction()) throw new DbException('Starting transaction error');
		foreach ($deletedModels as $deletedModel) {
			if (false === $deletedCount = $deletedModel->delete()) {
				$transaction->rollBack();
				return null;
			}
			$dc += $deletedCount;
		}
		if ($transactional) $transaction->commit();
		return $dc;
	}

}