<?php namespace Wc1c\Cml\Entities;

/**
 * OrderDocument
 *
 * @package Wc1c\Cml
 */
class OrderDocument extends Document
{
    /**
     * Получение продуктов документа
     *
     * @return false|array Ложь, массив всех реквизитов или значение конкретного реквизита
     */
    public function getProducts()
    {
        if(empty($this->data['products']))
        {
            return false;
        }

        return $this->data['products'];
    }
}