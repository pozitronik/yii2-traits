<?php
declare(strict_types = 1);

namespace pozitronik\traits\models;

use pozitronik\traits\traits\ActiveRecordTrait;
use yii\db\ActiveRecord as YiiActiveRecord;

/**
 * Class ActiveRecord
 * Всё, зачем нужен этот класс - чтобы IDE понимала, к чему прикрепляется трейт, и не требовалось описывать виртуальные интерфейсы
 */
class ActiveRecord extends YiiActiveRecord {
	use ActiveRecordTrait;

}