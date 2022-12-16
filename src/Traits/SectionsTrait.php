<?php namespace Wc1c\Main\Traits;

defined('ABSPATH') || exit;

/**
 * SectionsTrait
 *
 * @package Wc1c\Main\Traits
 */
trait SectionsTrait
{
	/**
	 * @var string
	 */
	private $section_key = 'section';

	/**
	 * @var array Sections
	 */
	private $sections = [];

	/**
	 * @var string Current section
	 */
	private $current_section = '';

	/**
	 * Get current section
	 *
	 * @return string
	 */
	public function getCurrentSection()
	{
		return $this->current_section;
	}

	/**
	 * Set current section
	 *
	 * @param string $current_section
	 */
	public function setCurrentSection($current_section)
	{
		$final = apply_filters('wc1c_admin_init_sections_current', $current_section);

		$this->current_section = $final;
	}

	/**
	 * Initializing current section
	 *
	 * @return string
	 */
	public function initCurrentSection()
	{
		$current_section = !empty($_GET[$this->getSectionKey()]) ? sanitize_title($_GET[$this->getSectionKey()]) : '';

		if($current_section !== '')
		{
			$this->setCurrentSection($current_section);
		}

		return $this->getCurrentSection();
	}

	/**
	 * Initialization
	 *
	 * @param array $sections
	 */
	public function initSections($sections = [])
	{
		$default_sections = [];

		if(!empty($sections) && is_array($sections))
		{
			$default_sections = array_merge($default_sections, $sections);
		}

		$final = apply_filters('wc1c_admin_init_sections', $default_sections);

		$this->setSections($final);
	}

	/**
	 * Get sections
	 *
	 * @return array
	 */
	public function getSections()
	{
		return apply_filters('wc1c_admin_get_sections', $this->sections);
	}

	/**
	 * Set sections
	 *
	 * @param array $sections
	 */
	public function setSections($sections)
	{
		// hook
		$sections = apply_filters('wc1c_admin_set_sections', $sections);

		$this->sections = $sections;
	}

	/**
	 * @return string
	 */
	public function getSectionKey()
	{
		return $this->section_key;
	}

	/**
	 * @param string $section_key
	 */
	public function setSectionKey($section_key)
	{
		$this->section_key = $section_key;
	}
}