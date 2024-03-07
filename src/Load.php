<?php
namespace Collei\Conf;

use Collei\Conf\Concerns\IniConfig;
use Collei\Conf\Concerns\JsonConfig;
use Collei\Conf\Support\Str;
use Collei\Conf\Support\Arr;
use Collei\Conf\Support\Env\Parser;

/**
 * Simple config file loader. 
 *
 * @author Alarido <alarido.su@gmail.com>
 * @author Collei Inc. <collei@collei.com.br>
 */
abstract class Load
{
	/**
	 * @var string
	 */
	public const TYPE_INI = 'ini';
	public const TYPE_JSON = 'json';
	public const TYPE_YAML = 'yaml';
	public const TYPE_XML = 'xml';

	/**
	 * @var string
	 */
	protected $type = null;

	/**
	 * @var mixed
	 */
	protected $settings;

	/**
	 * @var array
	 */
	protected $rawSettings;

	/**
	 * Loads a JSON config file.
	 *
	 * @param string $filename
	 * @return \Collei\Conf\Concerns\JsonConfig|false
	 */
	public static function fromJsonFile(string $filename)
	{
		if (! ($json = self::getFileContents($filename))) {
			return false;
		}

		if (! Str::isValidJson($json, $message)) {
			return false;
		}

		return new class($json) extends Load implements JsonConfig {};
	}

	/**
	 * Loads a INI config file.
	 *
	 * @param string $filename
	 * @return \Collei\Conf\Concerns\IniConfig|false
	 */
	public static function fromIniFile(string $filename)
	{
		if (! ($iniSource = self::getFileContents($filename))) {
			return false;
		}

		return new class($iniSource) extends Load implements IniConfig {};
	}

	/**
	 * Loads contents from the given file. Returns null if file is not found.
	 *
	 * @param string $filename
	 * @return string|null
	 */
	protected static function getFileContents(string $filename)
	{
		if (! file_exists($filename)) {
			return null;
		}

		return file_get_contents($filename);
	}

	/**
	 * Loads a config instance.
	 *
	 * @param string $contents
	 * @return void
	 */
	public function __construct(string $contents)
	{
		if ($this instanceof JsonConfig) {
			$this->loadJsonString($contents);
		}

		if ($this instanceof IniConfig) {
			$this->loadIniSource($contents);
		}
	}

	/**
	 * Parses the given JSON string and internalizes its values.
	 *
	 * @param string $json
	 * @return void
	 */
	protected function loadJsonString(string $json)
	{
		$decoded = json_decode($json, true, 512, JSON_OBJECT_AS_ARRAY);

		$this->rawSettings = $decoded;
		$this->settings = Arr::dot($decoded);

		$this->type = self::TYPE_JSON;
	}

	/**
	 * Parses the given INI source string and internalizes its values.
	 *
	 * @param string $iniSource
	 * @return void
	 */
	protected function loadIniSource(string $iniSource)
	{
		$parser = new Parser($iniSource);

		$parser->parse();

		$this->rawSettings = $parser;
		$this->settings = $parser->getEntries();
		
		$this->type = self::TYPE_INI;
	}

	/**
	 * Return a value (or values) upon the given $key, if any.
	 *
	 * @param string $key
	 * @param mixed $default = null
	 * @return mixed
	 */
	public function get(string $key, $default = null)
	{
		if (array_key_exists($key, $this->settings)) {
			return $this->settings[$key] ?? $default;
		}

		if ($this instanceof IniConfig) {
			if (Str::startsWith($key, '*.')) {
				return Arr::extractSuffixedArray($this->settings, $key);
			}
			//
			return Arr::extractPrefixedArray($this->settings, $key);
		}

		return Arr::get($this->rawSettings, $key, $default);
	}

	/**
	 * Return all parsed values as array with dotted keys.
	 *
	 * @return mixed
	 */
	public function all()
	{
		return $this->settings;
	}

	/**
	 * Return all parsed values as a multidimensional array (if it).
	 *
	 * @return mixed
	 */
	public function asArray()
	{
		return $this->rawSettings;
	}
}
