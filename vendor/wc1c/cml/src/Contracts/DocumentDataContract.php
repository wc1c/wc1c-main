<?php namespace Wc1c\Cml\Contracts;

/**
 * DocumentDataContract
 *
 * @package Wc1c\Cml
 */
interface DocumentDataContract extends DataContract
{
    /**
     * Получение уникального идентификатора документа в 1С
     *
     * @return false|string Document id
     */
    public function getId();

    /**
     * Получение уникального номера документа в 1С
     *
     * @return false|string Document id
     */
    public function getNumber();

    /**
     * Проверка на наличие реквизитов у документа, возможна проверка конкретного реквизита
     *
     * @param string $name Наименование реквизита
     *
     * @return bool Имеются ли реквизиты или конкретный реквизит
     */
    public function hasRequisites(string $name = ''): bool;

    /**
     * Получение реквизитов документа
     *
     * @param string $name Наименование реквизита для получения значения, опционально
     *
     * @return mixed Ложь, массив всех реквизитов или значение конкретного реквизита
     */
    public function getRequisites(string $name = '');
}