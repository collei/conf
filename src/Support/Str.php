<?php
namespace Collei\Conf\Support;

use RangeException;
use Throwable;

/**
 *	Reunites string helper functions
 *
 *	@author alarido <alarido.su@gmail.com>
 *	@since 2021-07-xx
 */
abstract class Str
{
	/**
	 *	Pluralizer schema for English nouns.
	 *
	 *	@var array
	 *	@link https://www.grammarly.com/blog/plural-nouns/
	 *	@since 2023-03-01
	 */
	protected const EN_PLURALIZE = [
		'rules:add' => [
			'as' => 'ses',	'es' => 'ses',	'os' => 'ses',
			'az' => 'zes',	'ez' => 'zes',	'iz' => 'zes',	'oz' => 'zes',	'uz' => 'zes',
			'ss' => 'es',	'sh' => 'es',	'ch' => 'es',
			'ay' => 's',	'ey' => 's',	'oy' => 's',	'uy' => 's',
			'ao' => 's',	'eo' => 's',	'io' => 's',	'uo' => 's',
			'x' => 'es',	'z' => 'es',	's' => 'es',	'o' => 'es',
		],
		'rules:change' => [
			'us' => 'i',	'is' => 'es',	'rion' => 'ria',
			'fe' => 'ves',	'f' => 'ves',
			'y' => 'ies',
		],
		'except' => [
			'roof' => 'roofs',
			'belief' => 'beliefs',
			'chef' => 'chefs',
			'chief' => 'chiefs',
			'photo' => 'photos',
			'piano' => 'pianos',
			'halo' => 'halos',
			'gas' => 'gases',
			'man' => 'men',
			'woman' => 'women',
			'child' => 'children',
			'person' => 'people',
			'foot' => 'feet',
			'tooth' => 'teeth',
			'mouse' => 'mice',
			'goose' => 'geese',
		],
		'invariant' => [
			'sheep','series','species','deer','fish','crossroads','aircraft',
		],
	];

	/**
	 *	Keep cache of resolved plurals.
	 *
	 *	@var array
	 */
	protected static $EN_PLURAL_CACHE = [];

	/**
	 *	Keep cache of resolved snake_case transforms.
	 *
	 *	@var array
	 */
	protected static $snakeCache = [];

	/**
	 *	Keep cache of resolved StudlyCase transforms.
	 *
	 *	@var array
	 */
	protected static $studlyCache = [];

	/**
	 *	Keep cache of resolved camelCase transforms.
	 *
	 *	@var array
	 */
	protected static $camelCache = [];

	/**
	 * Generate a random string, using a cryptographically secure 
	 * pseudorandom number generator (random_int)
	 *
	 * This function uses type hints now (PHP 7+ only), but it was originally
	 * written for PHP 5 as well.
	 * 
	 * For PHP 7, random_int is a PHP core function
	 * For PHP 5.x, depends on https://github.com/paragonie/random_compat
	 * 
	 *	@author	Scott Arciszewski <https://stackoverflow.com/users/2224584/scott-arciszewski>
	 *	@since	2015-06-29	from https://stackoverflow.com/questions/4356289/php-random-string-generator/31107425#31107425 (viewed 2021-11-02)
	 *
	 *	@param	int		$length		How many characters do we want?
	 *	@param	string	$keyspace	A string of all possible characters to select from
	 *	@return string
	 */
	public static function random(
		int $length = 64,
		string $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'
	): string {
		if ($length < 1) {
			throw new RangeException("Length must be a positive integer");
		}
		//
		$pieces = [];
		$max = \mb_strlen($keyspace, '8bit') - 1;
		//$max = \strlen($keyspace) - 1;
		//
		for ($i = 0; $i < $length; ++$i) {
			$pieces[] = $keyspace[\random_int(0, $max)];
		}
		//
		return \implode('', $pieces);
	}

	/**
	 *	Alias of Str::random() 
	 *
	 *	@param	int		$length		How many characters do we want?
	 *	@param	string	$keyspace	A string of all possible characters to select from
	 *	@return string
	 */
	public static function randomize(
		int $length = 64,
		string $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'
	) {
		return self::random($length, $keyspace);
	} 

	/**
	 *	Tells if thisComplexName is in camelCase
	 *
	 *	@param	string	$camel
	 *	@return	bool
	 */
	public static function isCamel(string $camel)
	{
		return 1 === \preg_match(
			'/^((\G(?!^)|\b[a-zA-Z][a-z\d]*)([A-Z][a-z\d]*)*|[a-z][a-z\d]*)$/',
			$camel
		);
	}

	/**
	 *	Converts this_complex_name to thisComplexName
	 *
	 *	@param	string	$snake
	 *	@return	string
	 */
	public static function toCamel(string $snake)
	{
		if (isset(static::$camelCache[$snake])) {
			return static::$camelCache[$snake];
		}
		//
		return static::$camelCache[$snake] = \lcfirst(
			\implode(
				'',
				\array_map(function($p){ return \ucfirst($p); }, \explode('_',$snake))
			)
		);
	}

	/**
	 *	Tells if this_complex_name is in snake format
	 *
	 *	@param	string	$snake
	 *	@return	bool
	 */
	public static function isSnake(string $snake)
	{
		return \preg_match('/^[a-z][a-z\d]*(_[a-z][a-z\d]*)*$/', $snake) === 1;
	}

	/**
	 *	Converts thisComplexName to this_complex_name
	 *
	 *	@param	string	$camel
	 *	@param	string	$delimiter = '_'
	 *	@return	string
	 */
	public static function toSnake(string $camel, string $delimiter = '_')
	{
		return static::snake($camel, $delimiter);
	}

	/**
	 *	Returns if the $str is quoted or not. Supported types: (") (')
	 *
	 *	@param	string	$str		the string
	 *	@param	string	$quoteType	which types to consider (empty = all)
	 *	@return	bool
	 */
	public static function isQuoted(string $str, string $quoteType = null)
	{
		$first = substr($str, 0, 1);
		$last = substr($str, -1);
		$length = strlen($str);
		//
		if (is_null($quoteType)) {
			return ('"' === $first || '\'' === $first)
				&& ($first === $last)
				&& ($length > 1);
		}
		//
		$quote = substr(($quoteType ?? ''), 0, 1);
		//
		return ($quote === $first) && ($first === $last) && ($length > 1);
	}

	/**
	 *	Returns if the $str is quoted with '' or not.
	 *
	 *	@param	string	$str		the string
	 *	@return	bool
	 */
	public static function isSingleQuoted(string $str)
	{
		return self::isQuoted($str, '\'');
	}

	/**
	 *	Returns if the $str is quoted with "" or not.
	 *
	 *	@param	string	$str		the string
	 *	@return	bool
	 */
	public static function isDoubleQuoted(string $str)
	{
		return self::isQuoted($str, '"');
	}

	/**
	 *	Returns the unclosed version of the given $str if it has parenthesis,
	 *	curly brackets etc.
	 *	supported types: () [] {} <> «»
	 *
	 *	@param	string	$str		the string
	 *	@param	string	...$with	which types to consider (empty = all)
	 *	@return	bool
	 */
	public static function isClosed(string $str, string ...$with)
	{
		if (empty($str)) {
			return false;
		}
		//
		$closes = [
			'pairs' => [
				['(',')'],['[',']'],['{','}'],['«','»'],['<','>']
			], 'types' => [
				'()','[]','{}','«»','<>'
			]
		];
		//
		$str = \trim($str);
		$with = empty($with) ? $closes['types'] : $with;
		//
		$q0 = \substr($str, 0, 1);
		$qf = \substr($str, -1, 1);
		//
		foreach ($closes['pairs'] as $n => $pair) {
			if (\in_array($q0.$qf, $with)) {
				if (\in_array([$q0,$qf], $closes['pairs'])) {
					return true;
				}
			}
		}
		//
		return false;
	}

	/**
	 *	Tells if the string starts with $prefix
	 *
	 *	@param	string	$str
	 *	@param	string	$prefix
	 *	@return	bool
	 */
	public static function startsWith(string $str, $prefix)
	{
		if (is_array($prefix)) {
			$results = false;
			//
			foreach ($prefix as $oneOf) {
				$results = $results || self::startsWith($str, $oneOf);
			}
			//
			return $results;
		}
		//
		return \str_starts_with($str, $prefix);
	}

	/**
	 *	Tells if the string ends with $suffix
	 *
	 *	@param	string	$str
	 *	@param	string	$suffix
	 *	@return	bool
	 */
	public static function endsWith(string $str, string $suffix)
	{
		return \str_ends_with($str, $suffix);
	}

	/**
	 *	Tells if something is inside the string
	 *
	 *	@param	string	$needle
	 *	@param	string	$haystack
	 *	@return	bool
	 */
	public static function has(string $needle, string $haystack)
	{
		return \strpos($haystack, $needle) !== FALSE;
	}

	/**
	 *	Splits a string using the delimiter as knife
	 *
	 *	@param	string	$knife
	 *	@param	string	$beefsteak
	 *	@return	array
	 */
	public static function explode(string $knife, string $beefsteak)
	{
		return \explode($knife, $beefsteak);
	}

	/**
	 *	String replacement
	 *
	 *	@param	string|array	$search
	 *	@param	string|array	$replacement
	 *	@param	string			$subject
	 *	@return	string
	 */
	public static function replace($search, $replacement, string $subject)
	{
		if (!\is_array($search) && \is_array($replacement)) {
			$replacement = \array_shift($replacement);
		}
		//
		if (!\is_array($replacement) && !\is_string($replacement)) {
			$replacement = '' . $replacement . '';
		}
		//
		return \str_replace($search, $replacement, $subject);
	}

	/**
	 *	Returns the unquoted version of the given $str if it has quotes
	 *
	 *	@param	string	$str	the string
	 *	@return	string
	 */
	public static function unquote(string $str)
	{
		if (empty($str)) {
			return $str;
		}
		//
		$str = \trim($str);
		$q0 = \substr($str, 0, 1);
		$qf = \substr($str, -1, 1);
		//
		if (\in_array($q0, ['"',"'"]) && ($q0 == $qf)) {
			return \substr($str, 1, -1);
		}
		//
		return $str;
	}

	/**
	 *	Returns the unclosed version of the given $str if it has parenthesis,
	 *	curly brackets etc.
	 *
	 *	@param	string	$str	the string
	 *	@return	string
	 */
	public static function unclose(string $str)
	{
		if (empty($str)) {
			return $str;
		}
		//
		$str = trim($str);
		$closeds = [
			['(',')'],
			['[',']'],
			['{','}'],
			['«','»'],
			['<','>'],
		];
		//
		$q0 = \substr($str, 0, 1);
		$qf = \substr($str, -1, 1);
		//
		if (\in_array([$q0,$qf], $closeds) && ($q0 == $qf))
		{
			return \substr($str, 1, -1);
		}
		//
		return $str;
	}

	/**
	 *	Returns the common string that is both the suffix of $front
	 *	and the prefix of $rear. If none, empty string is returned.
	 *
	 *	@author	Almir J.	<alarido.su@gmail.com>
	 *	@since	2021-11-16		
	 *	@param	string	$front
	 *	@param	string	$rear
	 *	@return	string
	 */
	public static function collision(string $front, string $rear)
	{
		// initialize the result
		$collided = '';
		// split both into array of char
		$ca_front = \str_split($front);
		$ca_rear = \str_split($rear);
		// define the $front boundary and current index
		$len_front = \count($ca_front);
		$f = 0;
		// helps stop looping at appropriate moment
		$found = false;		
		//
		while ($f < $len_front) {
			// the character
			$ch_fo = $ca_front[$f];
			// discards any partial result if the end of $front
			// was not yet reached
			if (!$found) {
				$collided = '';
			}
			// second pointer to the $front current char
			$delta = 1;
			//
			foreach ($ca_rear as $r => $ch_re) {
				//	if we reached the end of $front, it's time to stop 
				if (($f + $delta) >= $len_front) {
					$found = true;
					break;
				}
				// get the char to compare
				$ch_fo = $ca_front[$f + $delta];
				// updates the collided
				$collided .= $ch_re;
				// stop looping if both differ.
				// It makes discarding partial result
				// and try again at next $front char
				if ($ch_fo != $ch_re) {
					break;
				}
				// increase the delta
				++$delta;
			}
			// stop looping if we reached the end of $front
			if ($found) {
				break;
			}
			//
			++$f;
		}
		// if empty, it means no collision was found.
		return $collided;
	}

	/**
	 *	Returns whether is there a collision, i.e., a common string that is both
	 *	the suffix of $front and the prefix of $rear.
	 *
	 *	@author	Almir J.	<alarido.su@gmail.com>
	 *	@since	2021-11-16		
	 *	@param	string	$front
	 *	@param	string	$rear
	 *	@return	bool	true if a the collision exists, false otherwise		
	 */
	public static function collided(string $front, string $rear)
	{
		return self::collision($front, $rear) != '';
	}

	/**
	 *	Join two strings - $front and $rear - ignoring the collision,
	 *	i.e., it does not get repeated in the middle of the resulting string
	 *
	 *	@author	Almir J.	<alarido.su@gmail.com>
	 *	@since	2021-11-16		
	 *	@param	string	$front
	 *	@param	string	$rear
	 *	@return	bool	true if a the collision exists, false otherwise		
	 */
	public static function collapse(string $front, string $rear)
	{
		$collision = self::collision($front, $rear);
		//
		if ($collision == '') {
			return $front . $rear;
		}
		//
		return \substr($front, 0, \strlen($front) - \strlen($collision)) . $rear;
	}

	/**
	 *	Tokenize lines by spaces, except that tokens wrapped by "..." or '...'
	 *	will remain a single token, no matter how may spaces may exist inside
	 *
	 *	@param	string	$str	the string to be tokenized	
	 *	@return	array
	 */
	public static function tokenize(string $str)
	{
		$chars = \str_split($str);
		$tokens = [];
		$first_quote = '';
		$last = '';
		$current = '';
		// mini-function for reuse
		$break_if_nempty = function(&$items, &$item) {
			if (!empty($item)) {
				$items[] = $item;
				$item = '';
			}
		};
		//
		foreach ($chars as $ch) {
			if ($ch == ' ' || $ch == "\t") {
				if ($first_quote == '') {
					$break_if_nempty($tokens, $current);
				} else {
					$current .= $ch;
				}
			} elseif ($ch == '"' || $ch == "'") {
				if ($first_quote == $ch) {
					if ($last == '\\') {
						$current = \substr($current,0,-1) . $ch;
					} else {
						$first_quote = '';
						$break_if_nempty($tokens, $current);
					}
				} elseif ($first_quote == '') {
					$first_quote = $ch;
					$break_if_nempty($tokens, $current);
				} else {
					$current .= $ch;
				}
			} else {
				$current .= $ch;
			}
			// keep track of last char
			$last = $ch;
		}
		//
		$tokens[] = $current;
		//
		return $tokens;
	}

	/**
	 *	splits $str in two through $chars and removes just the first part	
	 *
	 *	@param	string	$str
	 *	@param	string	$chars
	 *	@return string	
	 */
	public static function stripAfter(string $str, string $chars)
	{
		$parts = \explode($chars, $str, 2);
		//
		return $parts[0];
	}

	/**
	 *	checks if a string is in the list
	 *
	 *	@param	string	$str
	 *	@param	array	$strings
	 *	@param	bool	$ignoreCase	true to case insensitive, false otherwise
	 *	@return	bool
	 */
	public static function in(string $str, array $strings, bool $ignoreCase = true)
	{
		if ($ignoreCase) {
			foreach ($strings as $string) {
				if (\strcasecmp($str, $string) == 0) {
					return true;
				}
			}
			return false;
		}
		//
		return \in_array($str, $strings);
	}

	/**
	 *	transform a string with line separators into an array with such lines
	 *
	 *	@param	string	$str
	 *	@return	array
	 */
	public static function linesToArray(string $str)
	{
		$string = \str_replace(["\r\n","\n","\r"],"\x01",$str);
		//
		return \explode("\x01", $string);
	}

	private static $DIACRITICS_MAP = [
		'A' 	=>	['Á','À','Â','Ä','Ã','Ā','Ă','Ą'],
		'a' 	=>	['á','à','â','ä','ã','ā','ă','ą'],
		'C' 	=>	['Ç','Ć','Ĉ','Ċ','Č'],
		'c' 	=>	['ç','ć','ĉ','ċ','č'],
		'D' 	=>	['Ď','Đ'],
		'd' 	=>	['ď','đ'],
		'E' 	=>	['É','È','Ê','Ë','Ē','Ĕ','Ė','Ę','Ě'],
		'e' 	=>	['é','è','ê','ë','ē','ĕ','ė','ę','ě'],
		'G' 	=>	['Ĝ','Ğ','Ġ','Ģ'],
		'g' 	=>	['ĝ','ğ','ġ','ģ'],
		'H' 	=>	['Ĥ','Ħ'],
		'h' 	=>	['ĥ','ħ'],
		'I' 	=>	['Í','Ì','Î','Ï','Ĩ','Ī','Ĭ','Į','İ'],
		'i' 	=>	['í','ì','î','ï','ĩ','ī','ĭ','į','ı'],
		'IJ' 	=>	['Ĳ'],
		'ij' 	=>	['ĳ'],
		'J' 	=>	['Ĵ'],
		'j' 	=>	['ĵ'],
		'K' 	=>	['Ķ'],
		'k' 	=>	['ķ','ĸ'],
		'L' 	=>	['Ĺ','Ļ','Ľ','Ŀ','Ł'],
		'l' 	=>	['ĺ','ļ','ľ','ŀ','ł'],
		'N' 	=>	['Ñ','Ń','Ņ','Ň'],
		'n' 	=>	['ñ','ń','ņ','ň','ŉ'],
		'NJ' 	=>	['Ŋ'],
		'nj' 	=>	['ŋ'],
		'O' 	=>	['Ó','Ò','Ô','Ö','Õ','Ō','Ŏ','Ő'],
		'o' 	=>	['ó','ò','ô','ö','õ','ō','ŏ','ő'],
		'OE' 	=>	['Œ'],
		'oe' 	=>	['œ'],
		'R' 	=>	['Ŕ','Ŗ','Ř'],
		'r' 	=>	['ŕ','ŗ','ř'],
		'S' 	=>	['Ś','Ŝ','Ş','Š'],
		's' 	=>	['ś','ŝ','ş','š'],
		'T' 	=>	['Ţ','Ť','Ŧ'],
		't' 	=>	['ţ','ť','ŧ'],
		'U' 	=>	['Ú','Ù','Û','Ü','Ũ','Ū','Ŭ','Ů','Ű','Ų'],
		'u' 	=>	['ú','ù','û','ü','ũ','ū','ŭ','ů','ű','ų'],
		'W' 	=>	['Ŵ'],
		'w' 	=>	['ŵ'],
		'Y' 	=>	['Ŷ','Ÿ'],
		'y' 	=>	['ŷ','ÿ'],
		'Z' 	=>	['Ź','Ż','Ž'],
		'z' 	=>	['ź','ż','ž'],
	];

	/**
	 *	removes diacritics from string
	 *
	 *	@param	string	$input
	 *	@return	string
	 */
	public static function cleanDiacritics(string $input)
	{
		$str = $input;
		//
		foreach (self::$DIACRITICS_MAP as $letr => $base) {
			$str = \str_replace($base, $letr, $str);
		}
		//
		return $str;
	}

	/**
	 *	insert line numbers at the start of each line in the given string
	 *
	 *	@param	string	$input
	 *	@return	string
	 */
	public static function withLineNumbers(string $input)
	{
		$lines = \explode("\n", \str_replace(["\r\n","\r"], "\n", $input));
		$formed = [];
		//
		foreach ($lines as $i => $line) {
			$formed[] = ($i+1) . "\t" . $line;
		}
		//
		return \implode("\r\n", $formed);
	}

	/**
	 *	Returns the string with the prefix, even repeated, removed
	 *
	 *	@param	string	$input
	 *	@param	string	$prefix
	 *	@return	string
	 */
	public static function trimPrefix(string $str, string $prefix)
	{
		while (Str::startsWith($str, $prefix)) {
			$str = \substr($str, \strlen($prefix));
		}
		//
		return $str;
	}

	/**
	 *	Returns the string with the prefix removed
	 *
	 *	@param	string	$input
	 *	@param	string	$prefix
	 *	@return	string
	 */
	public static function trimPrefixOnce(string $str, string $prefix)
	{
		if (Str::startsWith($str, $prefix)) {
			$str = \substr($str, \strlen($prefix));
		}
		//
		return $str;
	}

	/**
	 *	Returns the string with the suffix, even repeated, removed
	 *
	 *	@param	string	$input
	 *	@param	string	$suffix
	 *	@return	string
	 */
	public static function trimSuffix(string $str, string $suffix)
	{
		while (Str::endsWith($str, $suffix)) {
			$str = \substr($str, 0, \strlen($str) - \strlen($suffix));
		}
		//
		return $str;
	}

	/**
	 *	Returns the string with the suffix removed once
	 *
	 *	@param	string	$input
	 *	@param	string	$suffix
	 *	@return	string
	 */
	public static function trimSuffixOnce(string $str, string $suffix)
	{
		if (Str::endsWith($str, $suffix)) {
			$str = \substr($str, 0, \strlen($str) - \strlen($suffix));
		}
		//
		return $str;
	}

	/**
	 *	Returns the string with both prefix and suffix removed
	 *
	 *	@param	string	$input
	 *	@param	string	$prefix
	 *	@param	string	$suffix
	 *	@return	string
	 */
	public static function trimBoth(string $str, string $prefix, string $suffix)
	{
		return Str::trimSuffix(
			Str::trimPrefix($str, $prefix), $suffix
		);
	}

	/**
	 *	Returns the nth named variable argument in the string, path, etc
	 *
	 *		$str = "I want a {type} {flavor} {dessert}.";
	 *		$name = Str::getNamedArg($str, 1, '{', '}');
	 *			-> type
	 *		$name = Str::getNamedArg($str, 2, '{', '}');
	 *			-> flavor
	 *
	 *		$str = "I want a %type% {flavor} %dessert%.";
	 *		$name = Str::getNamedArg($str, 1, '%');
	 *			-> type
	 *		$name = Str::getNamedArg($str, 2, '%');
	 *			-> dessert
	 *
	 *	@param	string	$str
	 *	@param	int		$index
	 *	@param	mixed	$begin
	 *	@param	mixed	$end = null
	 *	@return	string
	 */
	public static function getNamedArg(
		string $str, int $index, $begin, $end = null
	) {
		if (empty($str) || empty($begin) || ($index < 1)) {
			return '';
		}

		$end = $end ?? $begin;
		$offset = 0;
		$name = '';

		while ($index >= 1) {
			if (($pos = \strpos($str, $begin, $offset)) !== false) {
				$offset = $pos + 1;
				//
				if (($pos2 = \strpos($str, $end, $pos + 1)) !== false) {
					$offset = $pos2 + 1;
					//
					$strBegin = $pos + \strlen($begin);
					$strEnd = $pos2;
					//
					$name = \substr($str, $strBegin, $strEnd - $strBegin);
				}
			}
			//
			--$index;
		}

		return $name;
	}

	/**
	 *	Counts the number of lines up to the specified $limit, where
	 *	$limit is the last character index of the string.
	 *	If $limit is omitted, the whole $text is considered.
	 *
	 *	@param	string	$text
	 *	@param	int		$limit
	 *	@return	int
	 */
	public static function countLines(string $text, int $limit = null)
	{
		if (!\is_null($limit) && ($limit > 0)) {
			return 1 + \substr_count(
				\substr($text, 0, $limit), PHP_EOL
			);
		}

		return 1 + \substr_count($text, PHP_EOL);
	}

	/**
	 *	Parses command-line argument lines into pieces.
	 *	Supports escaping the delimiter quote with a backslash
	 *	inside a quoted argument (e.g., " \" " or ' \' '),
	 *	depending on which quote is used.
	 *
	 *	@param	string	$thing
	 *	@return	array
	 */
	public static function parseArguments(string $thing)
	{
		$encloser = '';
		$piece = '';
		$last = '';
		$escaper = '\\';
		$pieces = [];
		//
		$chars = \mb_str_split($thing);
		//
		foreach ($chars as $ch) {
			if (($ch === "\"") || ($ch === '\'')) {
				if (empty($encloser)) {
					$encloser = $ch;
				} else {
					if ($ch === $encloser) {
						if ($last == $escaper) {
							$piece = \substr($piece, 0, -1) . $ch;
						} else {
							$encloser = '';
						}
					} else {
						$piece .= $ch;
					}
				}
			} elseif (\trim($ch) === '') {
				if (empty($encloser)) {
					$pieces[] = $piece;
					$piece = '';
				} else {
					$piece .= $ch;
				}
			} else {
				$piece .= $ch;
			}
			//
			$last = $ch;
		}
		//
		$pieces[] = $piece;
		//
		return $pieces;
	}

	public const PADDING_ALIGN_LEFT = -1;
	public const PADDING_ALIGN_CENTER = 0;
	public const PADDING_ALIGN_RIGHT = 1;

	/**
	 *	Returns a padded version of the string.
	 *
	 *	@param	string	$str
	 *	@param	int	$size
	 *	@param	int	$alignment = -1		(-1:left, 0:center, 1:right)
	 *	@param	string	$padWith = ' '	(uses only the first char of $padWith)
	 *	@return	string
	 */
	public static function pad(
		string $str,
		int $size,
		int $alignment = -1,
		string $padWith = ' '
	) {
		$len = \strlen($str);
		$size = \abs($size);
		$padWith = \substr($padWith, 0, 1);
		//
		if ($len == $size) {
			return $str;
		}
		//
		if ($len > $size) {
			if ($alignment > 0) {
				// right
				return \substr($str, -$size);
			} elseif ($alignment < 0) {
				// left
				return \substr($str, 0, $size);
			} else {
				// center
				$half = (int)(($len - $size) / 2);
				return \substr(
					\substr($str, $half, -$half), 0, $size
				);
			}
		}
		//
		if ($alignment > 0) {
			// right alignment
			while (\strlen($str) <= $size) {
				$str = $padWith . $str;
			}
		} elseif ($alignment < 0) {
			// left alignment
			while (\strlen($str) < $size) {
				$str .= $padWith;
			}
		} else {
			// center alignment
			while (\strlen($str) < $size) {
				$str = $padWith . $str . $padWith;
			}
		}
		//
		return \substr($str, 0, $size);
	}

	/**
	 *	Returns a string formed by the repeated char.
	 *
	 *	@param	string	$char
	 *	@param	int	$size
	 */
	public static function repeat(string $char, int $size)
	{
		return \str_repeat($char, $size);
	}

	/**
	 *	Converts a wildcarded string to its regex version.
	 *
	 *	@param	string	$wildcarded
	 *	@param	string	$delimiter = null
	 *	@return	string
	 */
	public static function wildcardToRegex(string $wildcarded, string $delimiter = null)
	{
		$regex = str_replace(['.','?','*'], ['\\.','.','.*'], $wildcarded);
		//
		if ($delimiter) {
			return $delimiter.$regex.$delimiter;
		}
		//
		return $regex;
	}

	/**
	 * Parse a Class[@]method style callback into class and method.
	 *
	 * @param  string  $callback
	 * @param  string|null  $default
	 * @return array<int, string|null>
	 */
	public static function parseCallback(string $callback, string $default = null)
	{
		return static::contains($callback, '@') ? explode('@', $callback, 2) : [$callback, $default];
	}

	/**
	 * Determine if a given string contains a given substring.
	 *
	 * @param  string  $haystack
	 * @param  string|string[]  $needles
	 * @return bool
	 */
	public static function contains(string $haystack, $needles)
	{
		foreach ((array) $needles as $needle) {
			if ($needle !== '' && mb_strpos($haystack, $needle) !== false) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Determine if a given string matches the wildcard pattern.
	 *
	 * @param  string  $wildcarded
	 * @param  string  $subject
	 * @return bool
	 */
	public static function is(string $wildcarded, string $subject, string $delimiter = null)
	{
		$regex = self::wildcardToRegex(
			$wildcarded, (empty($delimiter) ? '/' : substr($delimiter,0,1))
		);
		//
		return 1 === preg_match($regex, $subject);
	}

	/**
	 * Determine if a given string matches one of the wildcard patterns.
	 *
	 * @param  string  $subject
	 * @param  string  ...$wildcarded
	 * @return bool
	 */
	public static function isOneOf(string $subject, ...$wildcarded)
	{
		foreach ($wildcarded as $item) {
			if (self::is($item, $subject)) {
				return true;
			}
		}
		//
		return false;
	}

	/**
	 * Replaces ONLY the FIRST occurrence of $search in $subject.
	 *
	 * @param  string  $search
	 * @param  string  $replace
	 * @param  string  $subject
	 * @return string
	 */
	public static function replaceFirst(string $search, string $replace, string $subject)
	{
		// difference is heere related to the next
		$pos = strpos($subject, $search);
		//
		if (false !== $pos) {
			$subject = substr_replace($subject, $replace, $pos, strlen($search));
		}
		//
		return $subject;
	}

	/**
	 * Replaces ONLY the LAST occurrence of $search in $subject.
	 *
	 * @param  string  $search
	 * @param  string  $replace
	 * @param  string  $subject
	 * @return string
	 */
	public static function replaceLast(string $search, string $replace, string $subject)
	{
		// yea, there is a difference heere from the last
		$pos = strrpos($subject, $search);
		//
		if (false !== $pos) {
			$subject = substr_replace($subject, $replace, $pos, strlen($search));
		}
		//
		return $subject;
	}

	/**
	 *	Executes a str_replace with preserving certain sequences.
	 *
	 * @param  string  $search
	 * @param  string  $replace
	 * @param  string|array  $except
	 * @param  string  $subject
	 * @return string
	 */
	public static function replaceExcept(string $search, string $replace, $except, string $subject)
	{
		$replacers = [];
		//
		$data = $subject;
		//
		$preserved = is_array($except) ? $except : [$except];
		//
		foreach ($preserved as $item) {
			$replacer = '___replacer_'.count($replacers).'___';
			//
			$replacers[$replacer] = $item;
			//
			$data = str_replace($item, $replacer, $data);
		}
		//
		$data = str_replace($search, $replace, $data);
		//
		foreach ($replacers as $replacer => $item) {
			$data = str_replace($replacer, $item, $data);
		}
		//
		return $data;
	}

	/**
	 * Convert a string to snake case.
	 *
	 * @param  string  $value
	 * @param  string  $delimiter
	 * @return string
	 */
	public static function snake($value, $delimiter = '_')
	{
		$key = $value;

		if (isset(static::$snakeCache[$key][$delimiter])) {
			return static::$snakeCache[$key][$delimiter];
		}

		if (! ctype_lower($value)) {
			$value = preg_replace('/\s+/u', '', ucwords($value));

			$value = static::lower(preg_replace('/(.)(?=[A-Z])/u', '$1'.$delimiter, $value));
		}

		return static::$snakeCache[$key][$delimiter] = $value;
	}

	/**
	 * Convert a value to studly caps case.
	 *
	 * @param  string  $value
	 * @return string
	 */
	public static function studly($value)
	{
		$key = $value;

		if (isset(static::$studlyCache[$key])) {
			return static::$studlyCache[$key];
		}

		$value = ucwords(str_replace(['-', '_'], ' ', $value));

		return static::$studlyCache[$key] = str_replace(' ', '', $value);
	}

	/**
	 *	Returns a full-lowercased string.
	 *
	 *	@param	string	$string
	 *	@return	string
	 */
	public static function lower(string $string)
	{
		return \strtolower($string);
	}

	/**
	 *	Returns the pluralized version of English nouns.
	 *
	 *	@param	string	$singular
	 *	@return	string
	 */
	public static function pluralize(string $singular): string
	{
		$noun = static::lower($singular);
		//
		if (in_array($noun, static::EN_PLURALIZE['invariant'])) {
			return $singular;
		}
		//
		if ($plural = static::$EN_PLURAL_CACHE[$noun] ?? null) {
			return $plural;
		}
		//
		if ($plural = static::EN_PLURALIZE['except'][$noun] ?? null) {
			static::$EN_PLURAL_CACHE[$noun] = $plural;
			//
			return $plural;
		}
		//
		$lengths = [4, 2, 1];
		//
		foreach ($lengths as $len) {
			//
			// avoid crash it would happen when word ending tries
			// to be longer than the word itself !
			if ($len >= strlen($noun)) {
				continue;
			}
			//
			// let's prepare the ending
			$ending = substr($noun, -$len);
			//
			if ($res = static::EN_PLURALIZE['rules:add'][$ending] ?? null) {
				return static::$EN_PLURAL_CACHE[$noun] = $plural = $noun.$res;
				//
				return $plural;
			}
			//
			if ($res = static::EN_PLURALIZE['rules:change'][$ending] ?? null) {
				static::$EN_PLURAL_CACHE[$noun] = $plural = substr($noun, 0, -$len).$res;
				//
				return $plural;
			}
		}
		//
		static::$EN_PLURAL_CACHE[$noun] = $plural = $noun.'s';
		//
		return $plural;
	}

	/**
	 *	Normalize path for the current OS. 
	 *
	 *	@param	string|array	$path
	 *	@param	bool			$trim = false
	 *	@return	string
	 */
	public static function normalizePath($path, bool $trim = false)
	{
		if (is_array($path)) {
			$path = implode('/', $path);
		}
		//
		$result = preg_replace('#(\/+|\\+)#', DIRECTORY_SEPARATOR, $path);
		//
		return ($trim)
			? trim($result, DIRECTORY_SEPARATOR)
			: $result;
	}

	/**
	 *	Normalize URI. 
	 *
	 *	@param	string|array	$relative
	 *	@param	bool			$trim = false
	 *	@return	string
	 */
	public static function normalizeUri(
		$relative, bool $trimLeft = true, bool $trimRight = true
	) {
		if (is_array($relative)) {
			$relative = implode('/', $relative);
		}
		//
		$result = preg_replace('#(\/+|\\+)#', '/', $relative);
		//
		if ($trimLeft) {
			$result = ltrim($result, '/');
		}
		//
		if ($trimRight) {
			$result = rtrim($result, '/');
		}
		//
		return $result;
	}


	/**
	 *	Strip parentheses.
	 *
	 *	@param	string		$string
	 *	@return	string
	 */
	public static function stripParentheses(string $string)
	{
		if (Str::startsWith($string, '(') && Str::endsWith($string, ')')) {
			return substr($string, 1, -1);
		}
		//
		return $string;
	}

	/**
	 * Generate a URL friendly "slug" from a given string.
	 *
	 * @param  string  $title
	 * @param  string  $separator
	 * @param  string|null  $language
	 * @return string
	 */
	public static function slug($title, $separator = '-', $language = 'en')
	{
		$title = $language ? static::ascii($title, $language) : $title;

		// Convert all dashes/underscores into separator
		$flip = $separator === '-' ? '_' : '-';

		$title = preg_replace('!['.preg_quote($flip).']+!u', $separator, $title);

		// Replace @ with the word 'at'
		$title = str_replace('@', $separator.'at'.$separator, $title);

		// Remove all characters that are not the separator, letters, numbers, or whitespace.
		$title = preg_replace('![^'.preg_quote($separator).'\pL\pN\s]+!u', '', static::lower($title));

		// Replace all separator characters and whitespace by a single separator
		$title = preg_replace('!['.preg_quote($separator).'\s]+!u', $separator, $title);

		return trim($title, $separator);
	}

	/**
	 * Transliterate a UTF-8 value to ASCII.
	 *
	 * @param  string  $value
	 * @param  string  $language
	 * @return string
	 */
	public static function ascii($value, $language = 'en')
	{
		$languageSpecific = static::languageSpecificCharsArray($language);

		if (! is_null($languageSpecific)) {
			$value = str_replace($languageSpecific[0], $languageSpecific[1], $value);
		}

		foreach (static::charsArray() as $key => $val) {
			$value = str_replace($val, $key, $value);
		}

		return preg_replace('/[^\x20-\x7E]/u', '', $value);
	}

	/**
	 * Returns the language specific replacements for the ascii method.
	 *
	 * Note: Adapted from Stringy\Stringy.
	 *
	 * @see https://github.com/danielstjules/Stringy/blob/3.1.0/LICENSE.txt
	 *
	 * @param  string  $language
	 * @return array|null
	 */
	protected static function languageSpecificCharsArray($language)
	{
		static $languageSpecific;

		if (! isset($languageSpecific)) {
			$languageSpecific = [
				'bg' => [
					['х', 'Х', 'щ', 'Щ', 'ъ', 'Ъ', 'ь', 'Ь'],
					['h', 'H', 'sht', 'SHT', 'a', 'А', 'y', 'Y'],
				],
				'da' => [
					['æ', 'ø', 'å', 'Æ', 'Ø', 'Å'],
					['ae', 'oe', 'aa', 'Ae', 'Oe', 'Aa'],
				],
				'de' => [
					['ä',  'ö',  'ü',  'Ä',  'Ö',  'Ü'],
					['ae', 'oe', 'ue', 'AE', 'OE', 'UE'],
				],
				'he' => [
					['א', 'ב', 'ג', 'ד', 'ה', 'ו'],
					['ז', 'ח', 'ט', 'י', 'כ', 'ל'],
					['מ', 'נ', 'ס', 'ע', 'פ', 'צ'],
					['ק', 'ר', 'ש', 'ת', 'ן', 'ץ', 'ך', 'ם', 'ף'],
				],
				'ro' => [
					['ă', 'â', 'î', 'ș', 'ț', 'Ă', 'Â', 'Î', 'Ș', 'Ț'],
					['a', 'a', 'i', 's', 't', 'A', 'A', 'I', 'S', 'T'],
				],
			];
		}

		return $languageSpecific[$language] ?? null;
	}

	/**
	 * Returns the replacements for the ascii method.
	 *
	 * Note: Adapted from Stringy\Stringy.
	 *
	 * @see https://github.com/danielstjules/Stringy/blob/3.1.0/LICENSE.txt
	 *
	 * @return array
	 */
	protected static function charsArray()
	{
		static $charsArray;

		if (isset($charsArray)) {
			return $charsArray;
		}

		return $charsArray = [
			'0'	=> ['°', '₀', '۰', '０'],
			'1'	=> ['¹', '₁', '۱', '１'],
			'2'	=> ['²', '₂', '۲', '２'],
			'3'	=> ['³', '₃', '۳', '３'],
			'4'	=> ['⁴', '₄', '۴', '٤', '４'],
			'5'	=> ['⁵', '₅', '۵', '٥', '５'],
			'6'	=> ['⁶', '₆', '۶', '٦', '６'],
			'7'	=> ['⁷', '₇', '۷', '７'],
			'8'	=> ['⁸', '₈', '۸', '８'],
			'9'	=> ['⁹', '₉', '۹', '９'],
			'a'	=> ['à', 'á', 'ả', 'ã', 'ạ', 'ă', 'ắ', 'ằ', 'ẳ', 'ẵ', 'ặ', 'â', 'ấ', 'ầ', 'ẩ', 'ẫ', 'ậ', 'ā', 'ą', 'å', 'α', 'ά', 'ἀ', 'ἁ', 'ἂ', 'ἃ', 'ἄ', 'ἅ', 'ἆ', 'ἇ', 'ᾀ', 'ᾁ', 'ᾂ', 'ᾃ', 'ᾄ', 'ᾅ', 'ᾆ', 'ᾇ', 'ὰ', 'ά', 'ᾰ', 'ᾱ', 'ᾲ', 'ᾳ', 'ᾴ', 'ᾶ', 'ᾷ', 'а', 'أ', 'အ', 'ာ', 'ါ', 'ǻ', 'ǎ', 'ª', 'ა', 'अ', 'ا', 'ａ', 'ä', 'א'],
			'b'	=> ['б', 'β', 'ب', 'ဗ', 'ბ', 'ｂ', 'ב'],
			'c'	=> ['ç', 'ć', 'č', 'ĉ', 'ċ', 'ｃ'],
			'd'	=> ['ď', 'ð', 'đ', 'ƌ', 'ȡ', 'ɖ', 'ɗ', 'ᵭ', 'ᶁ', 'ᶑ', 'д', 'δ', 'د', 'ض', 'ဍ', 'ဒ', 'დ', 'ｄ', 'ד'],
			'e'	=> ['é', 'è', 'ẻ', 'ẽ', 'ẹ', 'ê', 'ế', 'ề', 'ể', 'ễ', 'ệ', 'ë', 'ē', 'ę', 'ě', 'ĕ', 'ė', 'ε', 'έ', 'ἐ', 'ἑ', 'ἒ', 'ἓ', 'ἔ', 'ἕ', 'ὲ', 'έ', 'е', 'ё', 'э', 'є', 'ə', 'ဧ', 'ေ', 'ဲ', 'ე', 'ए', 'إ', 'ئ', 'ｅ'],
			'f'	=> ['ф', 'φ', 'ف', 'ƒ', 'ფ', 'ｆ', 'פ', 'ף'],
			'g'	=> ['ĝ', 'ğ', 'ġ', 'ģ', 'г', 'ґ', 'γ', 'ဂ', 'გ', 'گ', 'ｇ', 'ג'],
			'h'	=> ['ĥ', 'ħ', 'η', 'ή', 'ح', 'ه', 'ဟ', 'ှ', 'ჰ', 'ｈ', 'ה'],
			'i'	=> ['í', 'ì', 'ỉ', 'ĩ', 'ị', 'î', 'ï', 'ī', 'ĭ', 'į', 'ı', 'ι', 'ί', 'ϊ', 'ΐ', 'ἰ', 'ἱ', 'ἲ', 'ἳ', 'ἴ', 'ἵ', 'ἶ', 'ἷ', 'ὶ', 'ί', 'ῐ', 'ῑ', 'ῒ', 'ΐ', 'ῖ', 'ῗ', 'і', 'ї', 'и', 'ဣ', 'ိ', 'ီ', 'ည်', 'ǐ', 'ი', 'इ', 'ی', 'ｉ', 'י'],
			'j'	=> ['ĵ', 'ј', 'Ј', 'ჯ', 'ج', 'ｊ'],
			'k'	=> ['ķ', 'ĸ', 'к', 'κ', 'Ķ', 'ق', 'ك', 'က', 'კ', 'ქ', 'ک', 'ｋ', 'ק'],
			'l'	=> ['ł', 'ľ', 'ĺ', 'ļ', 'ŀ', 'л', 'λ', 'ل', 'လ', 'ლ', 'ｌ', 'ל'],
			'm'	=> ['м', 'μ', 'م', 'မ', 'მ', 'ｍ', 'מ', 'ם'],
			'n'	=> ['ñ', 'ń', 'ň', 'ņ', 'ŉ', 'ŋ', 'ν', 'н', 'ن', 'န', 'ნ', 'ｎ', 'נ'],
			'o'	=> ['ó', 'ò', 'ỏ', 'õ', 'ọ', 'ô', 'ố', 'ồ', 'ổ', 'ỗ', 'ộ', 'ơ', 'ớ', 'ờ', 'ở', 'ỡ', 'ợ', 'ø', 'ō', 'ő', 'ŏ', 'ο', 'ὀ', 'ὁ', 'ὂ', 'ὃ', 'ὄ', 'ὅ', 'ὸ', 'ό', 'о', 'و', 'ို', 'ǒ', 'ǿ', 'º', 'ო', 'ओ', 'ｏ', 'ö'],
			'p'	=> ['п', 'π', 'ပ', 'პ', 'پ', 'ｐ', 'פ', 'ף'],
			'q'	=> ['ყ', 'ｑ'],
			'r'	=> ['ŕ', 'ř', 'ŗ', 'р', 'ρ', 'ر', 'რ', 'ｒ', 'ר'],
			's'	=> ['ś', 'š', 'ş', 'с', 'σ', 'ș', 'ς', 'س', 'ص', 'စ', 'ſ', 'ს', 'ｓ', 'ס'],
			't'	=> ['ť', 'ţ', 'т', 'τ', 'ț', 'ت', 'ط', 'ဋ', 'တ', 'ŧ', 'თ', 'ტ', 'ｔ', 'ת'],
			'u'	=> ['ú', 'ù', 'ủ', 'ũ', 'ụ', 'ư', 'ứ', 'ừ', 'ử', 'ữ', 'ự', 'û', 'ū', 'ů', 'ű', 'ŭ', 'ų', 'µ', 'у', 'ဉ', 'ု', 'ူ', 'ǔ', 'ǖ', 'ǘ', 'ǚ', 'ǜ', 'უ', 'उ', 'ｕ', 'ў', 'ü'],
			'v'	=> ['в', 'ვ', 'ϐ', 'ｖ', 'ו'],
			'w'	=> ['ŵ', 'ω', 'ώ', 'ဝ', 'ွ', 'ｗ'],
			'x'	=> ['χ', 'ξ', 'ｘ'],
			'y'	=> ['ý', 'ỳ', 'ỷ', 'ỹ', 'ỵ', 'ÿ', 'ŷ', 'й', 'ы', 'υ', 'ϋ', 'ύ', 'ΰ', 'ي', 'ယ', 'ｙ'],
			'z'	=> ['ź', 'ž', 'ż', 'з', 'ζ', 'ز', 'ဇ', 'ზ', 'ｚ', 'ז'],
			'aa'	=> ['ع', 'आ', 'آ'],
			'ae'	=> ['æ', 'ǽ'],
			'ai'	=> ['ऐ'],
			'ch'	=> ['ч', 'ჩ', 'ჭ', 'چ'],
			'dj'	=> ['ђ', 'đ'],
			'dz'	=> ['џ', 'ძ', 'דז'],
			'ei'	=> ['ऍ'],
			'gh'	=> ['غ', 'ღ'],
			'ii'	=> ['ई'],
			'ij'	=> ['ĳ'],
			'kh'	=> ['х', 'خ', 'ხ'],
			'lj'	=> ['љ'],
			'nj'	=> ['њ'],
			'oe'	=> ['ö', 'œ', 'ؤ'],
			'oi'	=> ['ऑ'],
			'oii'  => ['ऒ'],
			'ps'	=> ['ψ'],
			'sh'	=> ['ш', 'შ', 'ش', 'ש'],
			'shch' => ['щ'],
			'ss'	=> ['ß'],
			'sx'	=> ['ŝ'],
			'th'	=> ['þ', 'ϑ', 'θ', 'ث', 'ذ', 'ظ'],
			'ts'	=> ['ц', 'ც', 'წ'],
			'ue'	=> ['ü'],
			'uu'	=> ['ऊ'],
			'ya'	=> ['я'],
			'yu'	=> ['ю'],
			'zh'	=> ['ж', 'ჟ', 'ژ'],
			'(c)'  => ['©'],
			'A'	=> ['Á', 'À', 'Ả', 'Ã', 'Ạ', 'Ă', 'Ắ', 'Ằ', 'Ẳ', 'Ẵ', 'Ặ', 'Â', 'Ấ', 'Ầ', 'Ẩ', 'Ẫ', 'Ậ', 'Å', 'Ā', 'Ą', 'Α', 'Ά', 'Ἀ', 'Ἁ', 'Ἂ', 'Ἃ', 'Ἄ', 'Ἅ', 'Ἆ', 'Ἇ', 'ᾈ', 'ᾉ', 'ᾊ', 'ᾋ', 'ᾌ', 'ᾍ', 'ᾎ', 'ᾏ', 'Ᾰ', 'Ᾱ', 'Ὰ', 'Ά', 'ᾼ', 'А', 'Ǻ', 'Ǎ', 'Ａ', 'Ä'],
			'B'	=> ['Б', 'Β', 'ब', 'Ｂ'],
			'C'	=> ['Ç', 'Ć', 'Č', 'Ĉ', 'Ċ', 'Ｃ'],
			'D'	=> ['Ď', 'Ð', 'Đ', 'Ɖ', 'Ɗ', 'Ƌ', 'ᴅ', 'ᴆ', 'Д', 'Δ', 'Ｄ'],
			'E'	=> ['É', 'È', 'Ẻ', 'Ẽ', 'Ẹ', 'Ê', 'Ế', 'Ề', 'Ể', 'Ễ', 'Ệ', 'Ë', 'Ē', 'Ę', 'Ě', 'Ĕ', 'Ė', 'Ε', 'Έ', 'Ἐ', 'Ἑ', 'Ἒ', 'Ἓ', 'Ἔ', 'Ἕ', 'Έ', 'Ὲ', 'Е', 'Ё', 'Э', 'Є', 'Ə', 'Ｅ'],
			'F'	=> ['Ф', 'Φ', 'Ｆ'],
			'G'	=> ['Ğ', 'Ġ', 'Ģ', 'Г', 'Ґ', 'Γ', 'Ｇ'],
			'H'	=> ['Η', 'Ή', 'Ħ', 'Ｈ'],
			'I'	=> ['Í', 'Ì', 'Ỉ', 'Ĩ', 'Ị', 'Î', 'Ï', 'Ī', 'Ĭ', 'Į', 'İ', 'Ι', 'Ί', 'Ϊ', 'Ἰ', 'Ἱ', 'Ἳ', 'Ἴ', 'Ἵ', 'Ἶ', 'Ἷ', 'Ῐ', 'Ῑ', 'Ὶ', 'Ί', 'И', 'І', 'Ї', 'Ǐ', 'ϒ', 'Ｉ'],
			'J'	=> ['Ｊ'],
			'K'	=> ['К', 'Κ', 'Ｋ'],
			'L'	=> ['Ĺ', 'Ł', 'Л', 'Λ', 'Ļ', 'Ľ', 'Ŀ', 'ल', 'Ｌ'],
			'M'	=> ['М', 'Μ', 'Ｍ'],
			'N'	=> ['Ń', 'Ñ', 'Ň', 'Ņ', 'Ŋ', 'Н', 'Ν', 'Ｎ'],
			'O'	=> ['Ó', 'Ò', 'Ỏ', 'Õ', 'Ọ', 'Ô', 'Ố', 'Ồ', 'Ổ', 'Ỗ', 'Ộ', 'Ơ', 'Ớ', 'Ờ', 'Ở', 'Ỡ', 'Ợ', 'Ø', 'Ō', 'Ő', 'Ŏ', 'Ο', 'Ό', 'Ὀ', 'Ὁ', 'Ὂ', 'Ὃ', 'Ὄ', 'Ὅ', 'Ὸ', 'Ό', 'О', 'Ө', 'Ǒ', 'Ǿ', 'Ｏ', 'Ö'],
			'P'	=> ['П', 'Π', 'Ｐ'],
			'Q'	=> ['Ｑ'],
			'R'	=> ['Ř', 'Ŕ', 'Р', 'Ρ', 'Ŗ', 'Ｒ'],
			'S'	=> ['Ş', 'Ŝ', 'Ș', 'Š', 'Ś', 'С', 'Σ', 'Ｓ'],
			'T'	=> ['Ť', 'Ţ', 'Ŧ', 'Ț', 'Т', 'Τ', 'Ｔ'],
			'U'	=> ['Ú', 'Ù', 'Ủ', 'Ũ', 'Ụ', 'Ư', 'Ứ', 'Ừ', 'Ử', 'Ữ', 'Ự', 'Û', 'Ū', 'Ů', 'Ű', 'Ŭ', 'Ų', 'У', 'Ǔ', 'Ǖ', 'Ǘ', 'Ǚ', 'Ǜ', 'Ｕ', 'Ў', 'Ü'],
			'V'	=> ['В', 'Ｖ'],
			'W'	=> ['Ω', 'Ώ', 'Ŵ', 'Ｗ'],
			'X'	=> ['Χ', 'Ξ', 'Ｘ'],
			'Y'	=> ['Ý', 'Ỳ', 'Ỷ', 'Ỹ', 'Ỵ', 'Ÿ', 'Ῠ', 'Ῡ', 'Ὺ', 'Ύ', 'Ы', 'Й', 'Υ', 'Ϋ', 'Ŷ', 'Ｙ'],
			'Z'	=> ['Ź', 'Ž', 'Ż', 'З', 'Ζ', 'Ｚ'],
			'AE'	=> ['Æ', 'Ǽ'],
			'Ch'	=> ['Ч'],
			'Dj'	=> ['Ђ'],
			'Dz'	=> ['Џ'],
			'Gx'	=> ['Ĝ'],
			'Hx'	=> ['Ĥ'],
			'Ij'	=> ['Ĳ'],
			'Jx'	=> ['Ĵ'],
			'Kh'	=> ['Х'],
			'Lj'	=> ['Љ'],
			'Nj'	=> ['Њ'],
			'Oe'	=> ['Œ'],
			'Ps'	=> ['Ψ'],
			'Sh'	=> ['Ш', 'ש'],
			'Shch' => ['Щ'],
			'Ss'	=> ['ẞ'],
			'Th'	=> ['Þ', 'Θ', 'ת'],
			'Ts'	=> ['Ц'],
			'Ya'	=> ['Я', 'יא'],
			'Yu'	=> ['Ю', 'יו'],
			'Zh'	=> ['Ж'],
			' '	=> ["\xC2\xA0", "\xE2\x80\x80", "\xE2\x80\x81", "\xE2\x80\x82", "\xE2\x80\x83", "\xE2\x80\x84", "\xE2\x80\x85", "\xE2\x80\x86", "\xE2\x80\x87", "\xE2\x80\x88", "\xE2\x80\x89", "\xE2\x80\x8A", "\xE2\x80\xAF", "\xE2\x81\x9F", "\xE3\x80\x80", "\xEF\xBE\xA0"],
		];
	}

	/**
	 * Abbreviates path addresses.
	 *
	 * @param string $path
	 * @param int $limit = 70
	 * @return array
	 */
	public static function abbreviatePath(string $path, int $limit = 70)
	{
		// Optimize when path already fits the limit.  
		if (strlen($path) <= $limit) {
			return $path;
		}
		// Detect the separator based upon path format
		$separator = (
			(preg_match('/^\\\\{2}/', $path) === 1) || (preg_match('/^\w\:/', $path) === 1)
		) ? '\\' : '/';
		//
		// while path size does not fit the limit...
		while (($len = strlen($path)) > $limit) {
			// spot the middle point
			$middle = floor($len / 2);
			// set the shift factor if the separator is in the middle
			$shift = (substr($path,$middle,1) == $separator) ? 1 : 0;
			// initialize both sides
			$left = $right = $middle;
			//
			// scan for the innermost separator towards the left
			for ($i = $middle - $shift; $i > 0; $i--) {
				if (substr($path,$i,1) == $separator) {
					$left = $i;
					break;
				}
			}
			//
			// scan for the innermost separator towards the right
			for ($i = $middle + $shift; $i < $len; $i++) {
				if (substr($path,$i,1) == $separator) {
					$right = $i;
					break;
				}
			}
			//
			// replaces the marked substring with "pipe ellipsis"
			$path = substr_replace($path, '|...|', $left, $right - $left + 1);
		}
		//
		// replaces "pipe ellipsis" with the right one
		return str_replace('|...|', "{$separator}...{$separator}", $path);
	}

	/**
	 * Check if the given string is a valid JSON string.
	 *
	 * @param string $json
	 * @param string|\Throwable &$message = null
	 * @return bool
	 */
	public static function isValidJson(string $json, &$message = null)
	{
		$message = null;

		try {
			json_decode($json);

			if (json_last_error() === JSON_ERROR_NONE) {
				return true;
			} else {
				$message = json_last_error_msg();
			}
		} catch (Throwable $t) {
			$message = $t;

			return false;
		}

		return false;
	}

}

