<?php namespace Wc1c\Main\Schemas\Abstracts\Cml;

defined('ABSPATH') || exit;

/**
 * ReceiverAbstract
 *
 * @package Wc1c\Main\Abstracts\Cml
 */
abstract class ReceiverAbstract
{
    /**
     * @var string Current mode
     */
    public $mode = '';

    /**
     * @var string Current type
     */
    public $type = '';

    /**
     * @return array
     */
    public function detectModeAndType(): array
    {
        $data =
        [
            'mode' => '',
            'type' => ''
        ];

        if(!empty($this->getType()) && !empty($this->getMode()))
        {
            return
            [
                'mode' => $this->getMode(),
                'type' => $this->getType()
            ];
        }

        if(wc1c()->getVar($_GET['get_param'], '') !== '' || wc1c()->getVar($_GET['get_param?type'], '') !== '')
        {
            $output = [];
            if(isset($_GET['get_param']))
            {
                $get_param = ltrim(sanitize_text_field($_GET['get_param']), '?');
                parse_str($get_param, $output);
            }

            if(array_key_exists('mode', $output))
            {
                $data['mode'] = sanitize_key($output['mode']);
            }
            elseif(isset($_GET['mode']))
            {
                $data['mode'] = sanitize_key($_GET['mode']);
            }

            if(array_key_exists('type', $output))
            {
                $data['type'] = sanitize_key($output['type']);
            }
            elseif(isset($_GET['type']))
            {
                $data['type'] = sanitize_key($_GET['type']);
            }

            if($data['type'] === '')
            {
                $data['type'] = sanitize_key($_GET['get_param?type']);
            }
        }

        $this->setMode($data['mode']);
        $this->setType($data['type']);

        return $data;
    }

    /**
     * @return string
     */
    public function getMode(): string
    {
        return $this->mode;
    }

    /**
     * @param string $mode
     */
    public function setMode(string $mode)
    {
        $this->mode = $mode;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType(string $type)
    {
        $this->type = $type;
    }
}