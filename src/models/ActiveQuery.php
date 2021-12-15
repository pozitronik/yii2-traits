<?php
declare(strict_types = 1);

namespace pozitronik\traits\models;

use pozitronik\helpers\ArrayHelper;
use pozitronik\helpers\DateHelper;
use pozitronik\traits\traits\ActiveQueryTrait;
use Throwable;
use yii\db\ActiveQuery as YiiActiveQuery;

/**
 * Class ActiveQuery
 * Всё, зачем нужен этот класс - добавление трейта ActiveQueryTrait в результат возврата ActiveRecordTrait::find(),
 * плюс он помогает IDE понять связь трейта, и не требовать описания интерфейсов.
 */
class ActiveQuery extends YiiActiveQuery {
	use ActiveQueryTrait;

	/**
	 * Фильтровать выборку по попаданию между двумя датами, переданными одной строкой (из виджета DateRangePicker, например).
	 * @param string[]|string $field string: поле, значение которого проверяем на попадание в период, array: поля, содержащие даты начала и конца периода
	 * @param ?string $date Строковое представление периода
	 * @param ?string $datesDelimiter Разделитель дат в строке периода. Если null, считаем, что дата передана одним днём
	 * @param string $format php-формат дат в строке периода
	 * @return static
	 * @throws Throwable
	 *
	 * todo: обязательно написать тесты
	 */
	public function andFilterDateBetween(string|array $field, ?string $date, ?string $datesDelimiter = ' - ', string $format = 'Y-m-d'):ActiveQuery {
		if (null !== $date) {
			if (null === $datesDelimiter) {
				$beginDate = $date;
				$endDate = $beginDate;
			} else {
				if (2 !== count($dates = explode($datesDelimiter, $date))) return $this;//разделитель не найден
				$beginDate = ArrayHelper::getValue($dates, 0);
				$endDate = ArrayHelper::getValue($dates, 1);
			}
			if (DateHelper::isValidDate($beginDate, $format) && DateHelper::isValidDate($endDate, $format)) {/*Проверяем даты на валидность*/
				$beginDate .= ' 00:00:00';
				$endDate .= ' 23:59:59';
				(is_array($field))
					?$this->andWhere(['or', ['<=', $field[0], $beginDate], [$field[0] => null]])->andWhere(['or', ['>=', $field[1], $endDate], [$field[1] => null]])
					:$this->andFilterWhere(['between', $field, $beginDate, $endDate]);
			}
		}

		return $this;
	}
}