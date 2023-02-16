<?php namespace Wc1c\Cml\Entities;

defined('ABSPATH') || exit;

use Wc1c\Cml\Abstracts\DataAbstract;
use Wc1c\Cml\Contracts\DocumentDataContract;

/**
 * Document
 *
 * @package Wc1c\Cml
 */
class Document extends DataAbstract implements DocumentDataContract
{
	/**
	 * @var array
	 */
	public $data =
	[
		'id' => '',
        'number' => '',
		'requisites' => [],
	];

	/**
	 * @return string|false
	 */
	public function getId()
	{
        return $this->data['id'] ?? false;
    }

	/**
	 * @return string|false
	 */
	public function getNumber()
	{
        return $this->data['number'] ?? false;
    }

	/**
	 * @param $id
	 *
	 * @return mixed
	 */
	public function setId($id)
	{
		$this->data['id'] = $id;

		return $this->data['id'];
	}

    /**
     * @param $number
     *
     * @return mixed
     */
    public function setNumber($number)
    {
        $this->data['number'] = $number;

        return $this->data['number'];
    }

	/**
	 * Получение реквизитов документа
	 *
	 * @param string $name Наименование реквизита для получения значения, опционально
	 *
	 * @return false|array Ложь, массив всех реквизитов или значение конкретного реквизита
	 */
	public function getRequisites(string $name = '')
	{
		if(!$this->hasRequisites())
		{
			return false;
		}

		if('' !== $name)
		{
			if($this->hasRequisites($name))
			{
				return $this->data['requisites'][$name];
			}

			return false;
		}

		return $this->data['requisites'];
	}

	/**
	 * Проверка на наличие реквизитов у документа, возможна проверка конкретного реквизита
	 *
	 * @param string $name Наименование реквизита
	 *
	 * @return bool Имеются ли реквизиты
	 */
	public function hasRequisites(string $name = ''): bool
	{
		if(empty($this->data['requisites']))
		{
			return false;
		}

		if('' !== $name)
		{
			if(isset($this->data['requisites'][$name]))
			{
				return true;
			}

			return false;
		}

		return true;
	}
}