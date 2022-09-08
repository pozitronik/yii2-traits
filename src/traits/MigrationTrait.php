<?php
declare(strict_types = 1);

namespace pozitronik\traits\traits;

use yii\base\NotSupportedException;
use yii\db\ColumnSchemaBuilder;
use yii\db\Migration;

/**
 * Trait MigrationTrait
 */
trait MigrationTrait {
	/**
	 * Creates UUID column
	 * @return ColumnSchemaBuilder
	 * @throws NotSupportedException
	 */
	public function uuid():ColumnSchemaBuilder {
		/** @var Migration $this */
		return $this->getDb()->getSchema()->createColumnSchemaBuilder('uuid');
	}

	/**
	 * Creates a timestamptz column.
	 * @param int|null $precision column value precision. First parameter passed to the column type, e.g. TIMESTAMP(precision).
	 * This parameter will be ignored if not supported by the DBMS.
	 * @return ColumnSchemaBuilder the column instance which can be further customized.
	 * @throws NotSupportedException
	 */
	public function timestamptz(int $precision = null):ColumnSchemaBuilder {
		/** @var Migration $this */
		return $this->getDb()->getSchema()->createColumnSchemaBuilder('timestamptz', $precision);
	}
}