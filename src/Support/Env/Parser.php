<?php
namespace Collei\Conf\Support\Env;

use Collei\Conf\Support\Str;
use Collei\Conf\Support\Arr;

class Parser
{
	/**
	 * @var string
	 *
	 * the 'strange' subpattern group ($|...) helps to gather empty variable as as MYVAR=
	 */
	protected const ENTRY_PATTERN = '@^\s*(\w+)\s*=(.*$|\s*[\$!]?(\'((?:\\\\.|[^\'])*)\'|"((?:\\\\.|[^"])*)"|[^#\r\n]*).*)@m';

	/**
	 * @var string
	 */
	protected const SECTION_HEAD_PATTERN = '@^\[\s*([\w\.\*]+)\s*\]@m';

	/**
	 * @var string
	 */
	protected const ENTRY_EXPANDER = '/(\\${(\\w+)})/';

	/**
	 * @var array
	 */
	protected const CHAR_ESCAPES = [
		'from' => ['\r', '\n', '\t', '\0', '\"', "\\'"],
		'to' => ["\r", "\n", "\t", "\0", "\"", "'"],
	];

	/**
	 * @var array
	 */
	protected $parsedEntries = [];

	/**
	 * @var string
	 */
	protected $source;

	/**
	 * Does the source parsing into discrete variable units
	 *
	 * @return void
	 */
	protected function parseSource()
	{
		$sections = self::parseSourceSections($this->source);

		$this->parsedEntries = [];
		//
		foreach ($sections as $key => $section) {
			if (false !== preg_match_all(self::ENTRY_PATTERN, $section, $matches, PREG_SET_ORDER)) {
				foreach ($matches as $match) {
					list($raw, $name, $rawValue) = $match;
					//
					$value = $match[5] ?? $match[3] ?? $match[2] ?? $rawValue;
					//
					$name = empty($key) ? $name : "{$key}.{$name}";
					//
					$this->parsedEntries[$name] = $this->parseValue($value, $rawValue);
				}
			}
		}
	}

	/**
	 * Parse the given source into an associative array of discrete sections.
	 *
	 * Values before the first declared section are put in a separate one
	 * with an empty string key ('').
	 *
	 * @param stirng $source
	 * @return array
	 */
	protected function parseSourceSections(string $source)
	{
		$count = preg_match_all(self::SECTION_HEAD_PATTERN, $source, $section_names);

		if (0 == $count) {
			return ['' => $source];
		}

		$section_values = preg_split(self::SECTION_HEAD_PATTERN, $source);

		$section_names = $section_names[1];

		array_unshift($section_names, "");

		return Arr::combine($section_names, $section_values);
	}

	/**
	 * Does the value parsing on special characters \r, \t, \n, \0
	 *
	 * @param string $value
	 * @param string|null $rawValue
	 * @return mixed
	 */
	protected function parseValue(string $value, string $rawValue = null)
	{
		$value = trim($value);
		$flag = substr($rawValue ?? '', 0, 1);

		if (Str::isDoubleQuoted($value)) {
			$value = str_replace(self::CHAR_ESCAPES['from'], self::CHAR_ESCAPES['to'], $value);
			//
			$value = $this->expandVariables($value);
		} elseif ('$' === $flag || '!' === $flag) {
			$value = $this->expandVariables($value);
		}

		return $this->reconvertValue($value);
	}

	/**
	 * Does the variable conversion to int, float, etc., if possible.
	 *
	 * @param string $value
	 * @return mixed
	 */
	protected function reconvertValue($value)
	{
		if (empty($value) || ('""' === $value) || ("''" === $value)) {
			return "";
		}

		if (strcasecmp(trim($value), "null") == 0) {
			return null;
		}

		if (strcasecmp(trim($value), "true") == 0) {
			return true;
		}

		if (strcasecmp(trim($value), "false") == 0) {
			return false;
		}

		if (! is_numeric($value)) {
			return Str::unquote($value);
		}

		$number = (double) trim($value);

		$int = (int) $number;

		return ($int == $number) ? $int : $number;
	}

	/**
	 * Does the variable expansion
	 *
	 * @param string $value
	 * @return string
	 */
	protected function expandVariables(string $value)
	{
		if (false !== preg_match_all(self::ENTRY_EXPANDER, $value, $matches, PREG_SET_ORDER)) {
			$withExpanded = $value;
			//
			foreach ($matches as $match) {
				$raw = $match[0];
				$var = $match[2];
				$value = $match[3] ?? $var;
				//
				$result = $this->parsedEntries[$var] ?? $raw;
				//
				$withExpanded = Str::unquote(str_replace($raw, $result, $withExpanded));
			}
			//
			return $withExpanded;
		}

		return $value;
	}

	/**
	 * Initializes a new instance of the parser
	 *
	 * @param string|null $source
	 */
	public function __construct(string $source = null)
	{
		$this->source = $source ?? '';
		//
		return $this;
	}

	/**
	 * Creates a new parser and loads the specified string as source.
	 *
	 * @static
	 * @param string $source
	 * @return static
	 */
	public static function from(string $source)
	{
		return (new static())->setSource($source);
	}

	/**
	 * Sets the source to be parsed.
	 *
	 * @param string $source
	 * @return this
	 */
	public function setSource(string $source)
	{
		$this->source = $source;
		//
		return $this;
	}

	/**
	 * Returns the raw source.
	 *
	 * @return string
	 */
	public function getSource()
	{
		return $this->source;
	}

	/**
	 * Returns the parsed entries as array.
	 *
	 * @return array
	 */
	public function getEntries()
	{
		return $entries = $this->parsedEntries;
	}

	/**
	 * Starts the parsing
	 *
	 * @return this
	 */
	public function parse()
	{
		$this->parseSource();
		//
		return $this;
	}

}
