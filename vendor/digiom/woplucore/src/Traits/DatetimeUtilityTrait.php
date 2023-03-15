<?php namespace Digiom\Woplucore\Traits;

defined('ABSPATH') || exit;

use Exception;

/**
 * DatetimeUtilityTrait
 *
 * @package Digiom\Woplucore\Traits
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
	public function utilityStringToTimestamp(string $time_string, $from_timestamp = null): int
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
	public function utilityTimezoneString(): string
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
				if($city['timezone_id'] && (int) $city['offset'] === $utc_offset && (bool) date('I') === (bool) $city['dst'])
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
	 * @throws Exception
	 */
	public function utilityTimezoneOffset(): float
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
	 * @throws Exception
	 */
	public function utilityPrettyDate($date): string
	{
		if(!$date)
		{
			return __('not');
		}

		$timestamp_create = $this->utilityStringToTimestamp($date) + $this->utilityTimezoneOffset();

		return sprintf
		(
			__('%s <span class="time">in: %s</span>'),
			date_i18n('d/m/Y', $timestamp_create),
			date_i18n('H:i:s', $timestamp_create)
		);
	}
}