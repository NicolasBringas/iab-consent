<?php

namespace IABConsent;

class Bits
{
	/**
	 * @param $bitString
	 * @return array
	 */
	public static function decodeBitsToIds($bitString)
	{
		$index = 0;
		$reduce = function ($acc, $bit) use (&$index) {
			if ($bit === '1' && (array_search($index + 1, $acc) === false)) {
				$acc[] = $index + 1;
			}
			$index++;

			return $acc;
		};
		$bitExploded = str_split($bitString, 1);

		return array_reduce($bitExploded, $reduce, []);
	}

	/**
	 * @param $bitString
	 * @param $start
	 * @param $length
	 * @return \DateTime
	 * @throws \Exception
	 */
	private static function decodeBitsToDate($bitString, $start, $length)
	{
		$date = new \DateTime;
		$date->setTimestamp(self::decodeBitsToInt($bitString, $start, $length) / 10);

		return $date;
	}

	/**
	 * @param $bitString
	 * @return string
	 */
	private static function decodeBitsToLetter($bitString)
	{
		$letterCode = self::decodeBitsToInt($bitString);

		return strtolower(chr($letterCode + 65));
	}

	/**
	 * @param $bitString
	 * @param $start
	 * @return bool
	 */
	private static function decodeBitsToBool($bitString, $start)
	{
		return intval(substr($bitString, $start, 1), 2) === 1;
	}

	/**
	 * @param $count
	 * @param string $string
	 * @return string
	 */
	private static function repeat($count, $string = '0')
	{
		$padString = "";
		for ($i = 0; $i < $count; $i++) {
			$padString .= $string;
		}
		return $padString;
	}

	/**
	 * @param $string
	 * @param $padding
	 * @return string
	 */
	private static function padLeft($string, $padding)
	{
		return self::repeat(max([0, $padding])) . $string;
	}

	/**
	 * @param $string
	 * @param $padding
	 * @return string
	 */
	public static function padRight($string, $padding)
	{
		return $string . self::repeat(max([0, $padding]));
	}

	/**
	 * @param $bitString
	 * @param int $start
	 * @param int $length
	 * @return int
	 */
	private static function decodeBitsToInt($bitString, $start = 0, $length = 0)
	{
		if ($start === 0 && $length === 0) {
			return intval($bitString, 2);
		}

		return intval(substr($bitString, $start, $length), 2);
	}

	/**
	 * @param $number
	 * @param $numBits
	 * @return string
	 */
	private static function encodeIntToBits($number, $numBits)
	{
		$bitString = "";
		if (is_numeric($number)) {
			$bitString = decbin(intval($number, 10));
		}
		// Pad the string if not filling all bits
		if ($numBits >= strlen($bitString)) {
			$bitString = self::padLeft($bitString, $numBits - strlen($bitString));
		}
		// Truncate the string if longer than the number of bits
		if (strlen($bitString) > $numBits) {
			$bitString = substr($bitString, 0, $numBits);
		}

		return $bitString;
	}

	/**
	 * @param $value
	 * @return string
	 */
	private static function encodeBoolToBits($value)
	{
		return self::encodeIntToBits($value === true ? 1 : 0, 1);
	}

	/**
	 * @param $date
	 * @param $numBits
	 * @return string
	 */
	private static function encodeDateToBits($date, $numBits)
	{
		if ($date instanceof \DateTime) {
			return self::encodeIntToBits($date->getTimestamp() * 10, $numBits);
		}
		return self::encodeIntToBits($date, $numBits);
	}

	/**
	 * @param $letter
	 * @param $numBits
	 * @return string
	 */
	private static function encodeLetterToBits($letter, $numBits)
	{
		$upperLetter = strtoupper($letter);
		return self::encodeIntToBits(ord($upperLetter[0]) - 65, $numBits);
	}

	/**
	 * @param $language
	 * @param int $numBits
	 * @return string
	 */
	private static function encodeLanguageToBits($language, $numBits = 12)
	{
		return self::encodeLetterToBits(substr($language, 0, 1), $numBits / 2) . self::encodeLetterToBits(substr($language, 1), $numBits / 2);
	}

	/**
	 * @param string $string
	 * @param array $definitionMap
	 * @return array
	 * @throws \Exception
	 */
	public static function decodeFromBase64($string, $definitionMap = null)
	{
		if (is_null($definitionMap)) {
			$definitionMap = Definitions::getVendorVersionMap();
		}

		// add padding
		while (strlen($string) % 4 !== 0) {
			$string .= "=";
		}
		// replace unsafe characters
		$string = str_replace("-", "+", $string);
		$string = str_replace("_", "/", $string);

		$bytes = base64_decode($string);
		$inputBits = "";
		for ($i = 0; $i < strlen($bytes); $i++) {
			$bitString = decbin(ord($bytes[$i]));
			$inputBits .= self::padLeft($bitString, 8 - strlen($bitString));
		}

		return self::decodeConsentStringBitValue($inputBits, $definitionMap);
	}

	/**
	 * @param $bitString
	 * @param $definitionMap
	 * @return array
	 * @throws \Exception
	 */
	private static function decodeConsentStringBitValue($bitString, $definitionMap)
	{
		$version = self::decodeBitsToInt($bitString, 0, Definitions::getVersionNumBits());
		if (! is_int($version)) {
			throw new \Exception('ConsentString - Unknown version number in the string to decode');
		} else if (! isset(Definitions::getVendorVersionMap()[$version])) {
			throw new \Exception("ConsentString - Unsupported version {$version} in the string to decode");
		}
		$fields = $definitionMap[$version]['fields'];
		$decodedObject = self::decodeFields($bitString, $fields);

		return $decodedObject;
	}

	/**
	 * @param $input
	 * @param $field
	 * @return string
	 * @throws \Exception
	 */
	private static function encodeField($input, $field)
	{
		$name = $field['name'];
		$type = $field['type'];
		$numBits = $field['numBits'];
		$encoder = $field['encoder'];
		$validator = $field['validator'];

		if (is_callable($validator) && ! $validator($input)) {
			return '';
		}
		if (is_callable($encoder)) {
			return $encoder($input);
		}
		$bitCount = is_callable($numBits) ? $numBits($input) : $numBits;
		$inputValue = $input[$name];
		$fieldValue = is_null($inputValue) ? '' : $inputValue;

		switch ($type) {
			case 'int':
				return self::encodeIntToBits($fieldValue, $bitCount);
			case 'bool':
				return self::encodeBoolToBits($fieldValue);
			case 'date':
				return self::encodeDateToBits($fieldValue, $bitCount);
			case 'bits':
				return substr(self::padRight($fieldValue, $bitCount - strlen($fieldValue)), 0, $bitCount);
			case 'list':
				$reduce = function ($acc, $listValue) use ($field) {
					$ret = self::encodeFields($listValue, $field['fields']);
					return $acc . $ret;
				};
				/** @noinspection PhpParamsInspection */
				return array_reduce($fieldValue, $reduce, '');
			case 'language':
				return self::encodeLanguageToBits($fieldValue, $bitCount);
			default:
				throw new \Exception("ConsentString - Unknown field type {$type} for encoding");
		}
	}

	/**
	 * @param $input
	 * @param $fields
	 * @return string
	 */
	private static function encodeFields($input, $fields)
	{
		$reduce = function ($acc, $field) use ($input) {
			return $acc . self::encodeField($input, $field);
		};

		return array_reduce($fields, $reduce, '');
	}

	/**
	 * @param $data
	 * @param $definitionMap
	 * @return string
	 * @throws \Exception
	 */
	private static function encodeDataToBits($data, $definitionMap)
	{
		$version = $data['version'];
		if (! is_int($version)) {
			throw new \Exception("ConsentString - No version field to encode");
		} else if (! isset($definitionMap[$version])) {
			throw new \Exception("ConsentString - No definition for version {$version}");
		}
		$fields = $definitionMap[$version]['fields'];

		return self::encodeFields($data, $fields);
	}

	/**
	 * @param $data
	 * @param null $definitionMap
	 * @return string
	 * @throws \Exception
	 */
	public static function encodeToBase64($data, $definitionMap = null)
	{
		if (is_null($definitionMap)) {
			$definitionMap = Definitions::getVendorVersionMap();
		}
		$binaryValue = self::encodeDataToBits($data, $definitionMap);
		if ($binaryValue) {
			// Pad length to multiple of 8
			$paddedBinaryValue = self::padRight($binaryValue, 7 - ((strlen($binaryValue) + 7) % 8));
			$bytes = "";
			for ($i = 0; $i < strlen($paddedBinaryValue); $i += 8) {
				$bytes .= chr(intval(substr($paddedBinaryValue, $i, 8), 2));
			}
			// Make base64 string URL friendly
			return str_replace(
				"+",
				"-",
				str_replace(
					"/",
					"_",
					rtrim(base64_encode($bytes), '=')
				)
			);
		}
		return '';
	}

	/**
	 * @param $input
	 * @param $output
	 * @param $startPosition
	 * @param $field
	 * @return array
	 * @throws \Exception
	 */
	private static function decodeField($input, $output, $startPosition, $field)
	{
		$type = $field['type'];
		$numBits = $field['numBits'];
		$decoder = $field['decoder'];
		$validator = $field['validator'];
		$listCount = $field['listCount'];

		if (is_callable($validator)) {
			if (! $validator($output)) {
				// Not decoding this field so make sure we start parsing the next field at the same point
				return ['newPosition' => $startPosition];
			}
		}
		if (is_callable($decoder)) {
			return $decoder($input, $output, $startPosition);
		}
		if (is_callable($numBits)) {
			$bitCount = $numBits($output);
		} else {
			$bitCount = $numBits;
		}

		switch ($type) {
			case 'int':
				return ['fieldValue' => self::decodeBitsToInt($input, $startPosition, $bitCount)];
			case 'bool':
				return ['fieldValue' => self::decodeBitsToBool($input, $startPosition)];
			case 'date':
				return ['fieldValue' => self::decodeBitsToDate($input, $startPosition, $bitCount)];
			case 'bits':
				return ['fieldValue' => substr($input, $startPosition, $bitCount)];
			case 'list':
				return self::decodeList($input, $output, $startPosition, $field, $listCount);
			case 'language':
				return ['fieldValue' => self::decodeBitsToLanguage($input, $startPosition, $bitCount)];
			default:
				throw new \Exception("ConsentString - Unknown field type {$type} for decoding");
		}
	}

	/**
	 * @param $input
	 * @param $output
	 * @param $startPosition
	 * @param $field
	 * @param $listCount
	 * @return array
	 */
	private static function decodeList($input, $output, $startPosition, $field, $listCount)
	{
		$listEntryCount = 0;
		if (is_callable($listCount)) {
			$listEntryCount = $listCount($output);
		} else if (is_int($listCount)) {
			$listEntryCount = $listCount;
		}
		$newPosition = $startPosition;
		$fieldValue = [];
		for ($i = 0; $i < $listEntryCount; $i++) {
			$decodedFields = self::decodeFields($input, $field['fields'], $newPosition);
			$newPosition = $decodedFields['newPosition'];
			$fieldValue[] = $decodedFields;
		}

		return ['fieldValue' => $fieldValue, 'newPosition' => $newPosition];
	}

	/**
	 * @param $bitString
	 * @param $start
	 * @param $length
	 * @return string
	 */
	private static function decodeBitsToLanguage($bitString, $start, $length)
	{
		$languageBitString = substr($bitString, $start, $length);

		return self::decodeBitsToLetter(substr($languageBitString, 0, $length / 2)) . self::decodeBitsToLetter(substr($languageBitString, $length / 2));
	}

	/**
	 * @param $input
	 * @param $fields
	 * @param int $startPosition
	 * @return array
	 */
	private static function decodeFields($input, $fields, $startPosition = 0)
	{
		$position = $startPosition;
		$reducer = function ($acc, $field) use ($input, &$position) {
			$name = $field['name'];
			$numBits = $field['numBits'];
			$fieldDecoded = self::decodeField($input, $acc, $position, $field);
			$fieldValue = isset($fieldDecoded['fieldValue']) ? $fieldDecoded['fieldValue'] : null;
			$newPosition = isset($fieldDecoded['newPosition']) ? $fieldDecoded['newPosition'] : null;

			if (! is_null($fieldValue)) {
				$acc[$name] = $fieldValue;
			}
			if (! is_null($newPosition)) {
				$position = $newPosition;
			} else if (is_int($numBits)) {
				$position += $numBits;
			}

			return $acc;
		};
		$decodedObject = array_reduce($fields, $reducer, []);
		$decodedObject['newPosition'] = $position;

		return $decodedObject;
	}
}