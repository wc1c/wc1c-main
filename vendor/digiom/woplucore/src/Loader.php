<?php namespace Digiom\Woplucore;

defined('ABSPATH') || exit;

use Digiom\Woplucore\Interfaces\Loadable;

/**
 * Loader
 *
 * @package Digiom\Woplucore
 */
class Loader implements Loadable
{
	/**
	 * Base plugin file
	 *
	 * @var string
	 */
	protected $file = '';

	/**
	 * An associative array where the key is a namespace prefix and the value
	 * is an array of base directories for classes in that namespace.
	 *
	 * @var array
	 */
	protected $prefixes = [];

	/**
	 * Register loader with SPL autoloader stack.
	 *
	 * @param string $file
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function register(string $file)
	{
		$this->file = $file;

		spl_autoload_register([$this, 'loadClass']);
	}

	/**
	 * Adds a base directory for a namespace prefix.
	 *
	 * @param string $namespace The namespace prefix.
	 * @param string $directory A base directory for class files in the namespace.
	 * @param bool $prepend If true, prepend the base directory to the stack
	 * instead of appending it; this causes it to be searched first rather than last.
	 *
	 * @return void
	 */
	public function addNamespace(string $namespace, string $directory, bool $prepend = false)
	{
		// normalize namespace prefix
		$namespace = trim($namespace, '\\') . '\\';

		// normalize the base directory with a trailing separator
		$directory = rtrim($directory, DIRECTORY_SEPARATOR) . '/';

		// initialize the namespace prefix array
		if(isset($this->prefixes[$namespace]) === false)
		{
			$this->prefixes[$namespace] = [];
		}

		// retain the base directory for the namespace prefix
		if($prepend)
		{
			array_unshift($this->prefixes[$namespace], $directory);
		}
		else
		{
			array_push($this->prefixes[$namespace], $directory);
		}
	}

	/**
	 * Loads the class file for a given class name.
	 *
	 * @param string $class The fully-qualified class name.
	 *
	 * @return false|string The mapped file name on success, or boolean false on failure.
	 */
	public function loadClass(string $class)
	{
		// the current namespace prefix
		$prefix = $class;

		// work backwards through the namespace names of the fully-qualified
		// class name to find a mapped file name
		while(false !== $pos = strrpos($prefix, '\\'))
		{
			// retain the trailing namespace separator in the prefix
			$prefix = substr($class, 0, $pos + 1);

			// the rest is the relative class name
			$relative_class = substr($class, $pos + 1);

			// try to load a mapped file for the prefix and relative class
			$mapped_file = $this->loadMappedFile($prefix, $relative_class);
			if($mapped_file)
			{
				return $mapped_file;
			}

			// remove the trailing namespace separator for the next iteration of strrpos()
			$prefix = rtrim($prefix, '\\');
		}

		// never found a mapped file
		return false;
	}

	/**
	 * Load the mapped file for a namespace prefix and relative class.
	 *
	 * @param string $prefix The namespace prefix.
	 * @param string $relative_class The relative class name.
	 *
	 * @return false|string Boolean false if no mapped file can be loaded, or the name of the mapped file that was loaded.
	 */
	protected function loadMappedFile(string $prefix, string $relative_class)
	{
		// are there any base directories for this namespace prefix?
		if(isset($this->prefixes[$prefix]) === false)
		{
			return false;
		}

		// look through base directories for this namespace prefix
		foreach($this->prefixes[$prefix] as $base_dir)
		{
			// replace the namespace prefix with the base directory,
			// replace namespace separators with directory separators
			// in the relative class name, append with .php
			$file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

			// if the mapped file exists, require it
			if($this->requireFile($file))
			{
				// yes, we're done
				return $file;
			}
		}

		// never found it
		return false;
	}

	/**
	 * If a file exists, require it from the file system.
	 *
	 * @param string $file The file to require.
	 *
	 * @return bool True if the file exists, false if not.
	 */
	protected function requireFile(string $file): bool
	{
		if(file_exists($file))
		{
			try
			{
				require $file;
			}
			catch(\Error $e)
			{
				return false;
			}
			catch(\Exception $e)
			{
				return false;
			}
			return true;
		}

		return false;
	}

	/**
	 * Plugin activation
	 *
	 * @param callable $class
	 *
	 * @return void
	 */
	public function registerActivation($class)
	{
		register_activation_hook($this->file, $class);
	}

	/**
	 * Plugin deactivation
	 *
	 * @param callable $class
	 *
	 * @return void
	 */
	public function registerDeactivation($class)
	{
		register_deactivation_hook($this->file, $class);
	}

	/**
	 * Plugin uninstall
	 *
	 * @param callable $class
	 *
	 * @return void
	 */
	public function registerUninstall($class)
	{
		register_uninstall_hook($this->file, $class);
	}
}