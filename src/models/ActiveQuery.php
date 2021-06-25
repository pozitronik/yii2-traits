<?php
declare(strict_types = 1);

namespace pozitronik\traits\models;

use pozitronik\traits\traits\ActiveQueryTrait;
use yii\db\ActiveQuery as YiiActiveQuery;

/**
 * Class ActiveQuery
 * Всё, зачем нужен этот класс - добавление трейта ActiveQueryTrait в результат возврата ActiveRecordTrait::find(),
 * плюс он помогает IDE понять связь трейта, и не требовать описания интерфейсов.
 */
class ActiveQuery extends YiiActiveQuery {
	use ActiveQueryTrait;
}