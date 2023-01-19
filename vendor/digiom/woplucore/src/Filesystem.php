<?php namespace Digiom\Woplucore;

defined('ABSPATH') || exit;

use ErrorException;
use FilesystemIterator;
use RuntimeException;
use Digiom\Woplucore\Traits\SingletonTrait;

/**
 * Filesystem
 *
 * @package Digiom\Woplucore
 */
class Filesystem
{
	use SingletonTrait;

	/**
	 * Determine if a file or directory exists.
	 *
	 * @param string $path
	 *
	 * @return bool
	 */
	public function exists($path)
	{
		return file_exists($path);
	}

	/**
	 * Determine if a file or directory is missing.
	 *
	 * @param string $path
	 *
	 * @return bool
	 */
	public function missing(string $path): bool
	{
		return !$this->exists($path);
	}

	/**
	 * Get the contents of a file.
	 *
	 * @param string $path
	 * @param bool $lock
	 *
	 * @return string
	 * @throws RuntimeException
	 */
	public function get(string $path, bool $lock = false): string
	{
		if($this->isFile($path))
		{
			return $lock ? $this->sharedGet($path) : file_get_contents($path);
		}

		throw new RuntimeException('File does not exist at path: ' . $path);
	}

	/**
	 * Get contents of a file with shared access.
	 *
	 * @param string $path
	 *
	 * @return string
	 */
	public function sharedGet(string $path): string
	{
		$contents = '';
		$handle = fopen($path, 'rb');

		if($handle)
		{
			try
			{
				if(flock($handle, LOCK_SH))
				{
					clearstatcache(true, $path);
					$contents = fread($handle, $this->size($path) ?: 1);
					flock($handle, LOCK_UN);
				}
			}
			finally
			{
				fclose($handle);
			}
		}

		return $contents;
	}

	/**
	 * Get the MD5 hash of the file at the given path.
	 *
	 * @param string $path
	 *
	 * @return string
	 */
	public function hash(string $path): string
	{
		return md5_file($path);
	}

	/**
	 * Write the contents of a file.
	 *
	 * @param string $file_path
	 * @param string $contents
	 * @param bool $lock
	 *
	 * @return int|bool
	 */
	public function put(string $file_path, string $contents, bool $lock = false)
	{
		return file_put_contents($file_path, $contents, $lock ? LOCK_EX : 0);
	}

	/**
	 * Replace a given string within a given file.
	 *
	 * @param array|string $search
	 * @param array|string $replace
	 * @param string $file_path
	 *
	 * @return void
	 */
	public function replaceInFile($search, $replace, string $file_path)
	{
		$this->put($file_path, str_replace($search, $replace, file_get_contents($file_path)));
	}

	/**
	 * Prepend to a file.
	 *
	 * @param string $file_path
	 * @param string $data
	 *
	 * @return int
	 */
	public function prepend(string $file_path, string $data)
	{
		if($this->exists($file_path))
		{
			return $this->put($file_path, $data . $this->get($file_path));
		}

		return $this->put($file_path, $data);
	}

	/**
	 * Append to a file.
	 *
	 * @param string $file_path
	 * @param string $data
	 *
	 * @return int
	 */
	public function append(string $file_path, string $data)
	{
		return $this->put($file_path, $data, FILE_APPEND);
	}

	/**
	 * Get or set UNIX mode of a file or directory.
	 *
	 * @param string $path
	 * @param int|null $mode
	 *
	 * @return bool|string
	 */
	public function chmod(string $path, $mode = null)
	{
		if($mode)
		{
			return chmod($path, $mode);
		}

		return substr(sprintf('%o', fileperms($path)), -4);
	}

	/**
	 * Move a file to a new location.
	 *
	 * @param string $path
	 * @param string $target
	 *
	 * @return bool
	 */
	public function move(string $path, string $target)
	{
		return rename($path, $target);
	}

	/**
	 * Delete the file at a given path.
	 *
	 * @param string|array $paths
	 *
	 * @return bool
	 */
	public function delete($paths): bool
	{
		$paths = is_array($paths) ? $paths : func_get_args();
		$success = true;

		foreach($paths as $path)
		{
			try
			{
				if(@unlink($path))
				{
					clearstatcache(false, $path);
					continue;
				}

				$success = false;
			}
			catch(ErrorException $e)
			{
				$success = false;
			}
		}

		return $success;
	}

	/**
	 * Copy a file to a new location.
	 *
	 * @param string $path
	 * @param string $target
	 *
	 * @return bool
	 */
	public function copy(string $path, string $target): bool
	{
		return copy($path, $target);
	}

	/**
	 * Create a symlink to the target file or directory. On Windows, a hard link is created if the target is a file.
	 *
	 * @param string $target
	 * @param string $link
	 *
	 * @return bool
	 */
	public function link(string $target, string $link): bool
	{
		if(!$this->isOsWindows())
		{
			return symlink($target, $link);
		}

		$mode = $this->isDirectory($target) ? 'J' : 'H';

		$result = exec("mklink /{$mode} " . escapeshellarg($link) . ' ' . escapeshellarg($target));

		if($result)
		{
			return true;
		}

		return false;
	}

	/**
	 * Extract the file name from a file path.
	 *
	 * @param string $path
	 *
	 * @return string
	 */
	public function name(string $path): string
	{
		return pathinfo($path, PATHINFO_FILENAME);
	}

	/**
	 * Extract the trailing name component from a file path.
	 *
	 * @param string $path
	 *
	 * @return string
	 */
	public function basename(string $path): string
	{
		return pathinfo($path, PATHINFO_BASENAME);
	}

	/**
	 * Extract the parent directory from a file path.
	 *
	 * @param string $path
	 *
	 * @return string
	 */
	public function dirname(string $path): string
	{
		return pathinfo($path, PATHINFO_DIRNAME);
	}

	/**
	 * Extract the file extension from a file path.
	 *
	 * @param string $path
	 *
	 * @return string
	 */
	public function extension(string $path): string
	{
		return pathinfo($path, PATHINFO_EXTENSION);
	}

	/**
	 * Get the file type of given file.
	 *
	 * @param string $path
	 *
	 * @return string
	 */
	public function type(string $path): string
	{
		return filetype($path);
	}

	/**
	 * Get the mime-type of a given file.
	 *
	 * @param string $path
	 *
	 * @return string|false
	 */
	public function mimeType($path)
	{
		return finfo_file(finfo_open(FILEINFO_MIME_TYPE), $path);
	}

	/**
	 * Get the file size of a given file.
	 *
	 * @param string $path
	 *
	 * @return int
	 */
	public function size(string $path): int
	{
		return filesize($path);
	}

	/**
	 * Get the file's last modification time.
	 *
	 * @param string $path
	 *
	 * @return int|false
	 */
	public function lastModified(string $path)
	{
		return filemtime($path);
	}

	/**
	 * Determine if the given path is a directory.
	 *
	 * @param string $directory
	 *
	 * @return bool
	 */
	public function isDirectory(string $directory): bool
	{
		return is_dir($directory);
	}

	/**
	 * Determine if the given path is readable.
	 *
	 * @param string $path
	 *
	 * @return bool
	 */
	public function isReadable(string $path): bool
	{
		return is_readable($path);
	}

	/**
	 * Determine if the given path is writable.
	 *
	 * @param string $path
	 *
	 * @return bool
	 */
	public function isWritable(string $path): bool
	{
		return is_writable($path);
	}

	/**
	 * Determine if the given path is a file.
	 *
	 * @param string $file
	 *
	 * @return bool
	 */
	public function isFile(string $file): bool
	{
		return is_file($file);
	}

	/**
	 * Find path names matching a given pattern.
	 *
	 * @param string $pattern
	 * @param int $flags
	 *
	 * @return array|false
	 */
	public function glob(string $pattern, int $flags = 0)
	{
		return glob($pattern, $flags);
	}

	/**
	 * Create a directory.
	 *
	 * @param string $path
	 * @param int $mode
	 * @param bool $recursive
	 * @param bool $force
	 *
	 * @return bool
	 */
	public function makeDirectory(string $path, int $mode = 0755, bool $recursive = false, bool $force = false): bool
	{
		if($force)
		{
			return @mkdir($path, $mode, $recursive);
		}

		return mkdir($path, $mode, $recursive);
	}

	/**
	 * Move a directory.
	 *
	 * @param string $from
	 * @param string $to
	 * @param bool $overwrite
	 *
	 * @return bool
	 */
	public function moveDirectory(string $from, string $to, bool $overwrite = false): bool
	{
		if($overwrite && $this->isDirectory($to) && !$this->deleteDirectory($to))
		{
			return false;
		}

		return @rename($from, $to) === true;
	}

	/**
	 * Ensure a directory exists.
	 *
	 * @param string $path
	 * @param int $mode
	 * @param bool $recursive
	 *
	 * @return void
	 */
	public function ensureDirectoryExists(string $path, int $mode = 0755, bool $recursive = true)
	{
		if(!$this->isDirectory($path))
		{
			$this->makeDirectory($path, $mode, $recursive);
		}
	}

	/**
	 * Copy a directory from one location to another.
	 *
	 * @param string $directory
	 * @param string $destination
	 * @param int|null $options
	 *
	 * @return bool
	 */
	public function copyDirectory(string $directory, string $destination, $options = null): bool
	{
		if(!$this->isDirectory($directory))
		{
			return false;
		}

		$options = $options ?: FilesystemIterator::SKIP_DOTS;

		$this->ensureDirectoryExists($destination, 0777);

		$items = new FilesystemIterator($directory, $options);

		foreach($items as $item)
		{
			$target = $destination . '/' . $item->getBasename();

			if($item->isDir())
			{
				if(!$this->copyDirectory($item->getPathname(), $target, $options))
				{
					return false;
				}

				continue;
			}

			if(!$this->copy($item->getPathname(), $target))
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * Recursively delete a directory.
	 * The directory itself may be optionally preserved.
	 *
	 * @param string $directory
	 * @param bool $preserve
	 *
	 * @return bool
	 */
	public function deleteDirectory(string $directory, bool $preserve = false): bool
	{
		if(!$this->isDirectory($directory))
		{
			return false;
		}

		$items = new FilesystemIterator($directory);

		foreach($items as $item)
		{
			if($item->isDir() && !$item->isLink())
			{
				$this->deleteDirectory($item->getPathname());

				continue;
			}

			$this->delete($item->getPathname());
		}

		if(!$preserve)
		{
			@rmdir($directory);
		}

		return true;
	}

	/**
	 * Remove all the directories within a given directory.
	 *
	 * @param string $directory
	 *
	 * @return bool
	 */
	public function deleteDirectories(string $directory): bool
	{
		$allDirectories = $this->directories($directory);

		if(!empty($allDirectories))
		{
			foreach($allDirectories as $directoryName)
			{
				$this->deleteDirectory($directoryName);
			}
			return true;
		}
		return false;
	}

	/**
	 * Get all the directories within a given directory.
	 *
	 * @param string $directory
	 *
	 * @return array
	 */
	public function directories(string $directory)
	{
		return $this->glob($directory . '/*', GLOB_ONLYDIR);
	}

	/**
	 * Get an array of files in a directory.
	 *
	 * @param string $directory
	 *
	 * @return array
	 */
	public function files(string $directory): array
	{
		return array_filter($this->glob($directory . '/*'),'is_file');
	}

	/**
	 * Empty the specified directory of all files and folders.
	 *
	 * @param string $directory
	 *
	 * @return bool
	 */
	public function cleanDirectory(string $directory): bool
	{
		return $this->deleteDirectory($directory, true);
	}

	/**
	 * @return boolean
	 */
	private function isOsWindows(): bool
	{
		return defined('PHP_WINDOWS_VERSION_MAJOR');
	}
}