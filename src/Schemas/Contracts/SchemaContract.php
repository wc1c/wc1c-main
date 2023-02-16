<?php namespace Wc1c\Main\Schemas\Contracts;

defined('ABSPATH') || exit;

/**
 * SchemaContract
 *
 * @package Wc1c\Main\Schemas
 */
interface SchemaContract
{
    /**
     * @return mixed
     */
    public function init();

    /**
     * @param $id
     *
     * @return SchemaContract
     */
    public function setId($id): SchemaContract;

    /**
     * @param bool $lower
     *
     * @return string
     */
    public function getId(bool $lower = true): string;

    /**
     * @param string $name
     *
     * @return SchemaContract
     */
    public function setName(string $name): SchemaContract;

    /**
     * @return string
     */
    public function getName(): string;

    /**
     * @param string $description
     *
     * @return SchemaContract
     */
    public function setDescription(string $description): SchemaContract;

    /**
     * @return string
     */
    public function getDescription(): string;

    /**
     * @param array $options
     *
     * @return SchemaContract
     */
    public function setOptions(array $options): SchemaContract;

    /**
     * @param string $key
     * @param $default
     *
     * @return mixed
     */
    public function getOptions(string $key = '', $default = null);
}