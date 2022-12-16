<?php namespace Wc1c\Main\Traits;

defined('ABSPATH') || exit;

/**
 * DatetimeUtilityTrait
 *
 * @package Wc1c\Main\Traits
 */
trait DatetimeUtilityTrait
{
	/**
	 * Convert mysql datetime to PHP timestamp, forcing UTC. Wrapper for strtotime
	 *
	 * @param string $time_string Time string
	 * @param int|null $from_timestamp Timestamp to convert from
	 *
	 * @return int
	 */
	public function utilityStringToTimestamp($time_string, $from_timestamp = null)
	{
		$original_timezone = date_default_timezone_get();

		date_default_timezone_set('UTC');

		if(null === $from_timestamp)
		{
			$next_timestamp = strtotime($time_string);
		}
		else
		{
			$next_timestamp = strtotime($time_string, $from_timestamp);
		}

		date_default_timezone_set($original_timezone);

		return $next_timestamp;
	}

	/**
	 * Helper to retrieve the timezone string for a site until
	 *
	 * @return string PHP timezone string for the site
	 */
	public function utilityTimezoneString()
	{
		// If site timezone string exists, return it
		$timezone = get_option('timezone_string');

		if($timezone)
		{
			return $timezone;
		}

		// Get UTC offset, if it isn't set then return UTC
		$utc_offset = (int) get_option('gmt_offset', 0);
		if(0 === $utc_offset)
		{
			return 'UTC';
		}

		// Adjust UTC offset from hours to seconds
		$utc_offset *= 3600;

		// Attempt to guess the timezone string from the UTC offset
		$timezone = timezone_name_from_abbr('', $utc_offset);
		if($timezone)
		{
			return $timezone;
		}

		// Last try, guess timezone string manually
		foreach(timezone_abbreviations_list() as $abbr)
		{
			foreach($abbr as $city)
			{
				// WordPress restrict the use of date(), since it's affected by timezone settings, but in this case is just what we need to guess the correct timezone
				if((bool) date('I') === (bool) $city['dst'] && $city['timezone_id'] && (int) $city['offset'] === $utc_offset)
				{
					return $city['timezone_id'];
				}
			}
		}

		return 'UTC';
	}

	/**
	 * Get timezone offset in seconds
	 *
	 * @return float
	 */
	public function utilityTimezoneOffset()
	{
		$timezone = get_option('timezone_string');

		if($timezone)
		{
			return (new \DateTimeZone($timezone))->getOffset(new \DateTime('now'));
		}

		return (float) get_option('gmt_offset', 0) * HOUR_IN_SECONDS;
	}

	/**
	 * @param $date
	 *
	 * @return string
	 */
	public function utilityPrettyDate($date)
	{
		if(!$date)
		{
			return __('not', 'wc1c-main');
		}

		$timestamp_create = $this->utilityStringToTimestamp($date) + $this->utilityTimezoneOffset();

		return sprintf
		(
			__('%s <span class="time">in: %s</span>', 'wc1c-main'),
			date_i18n('d/m/Y', $timestamp_create),
			date_i18n('H:i:s', $timestamp_create)
		);
	}
}