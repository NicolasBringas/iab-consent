<?php

use PHPUnit\Framework\TestCase;
use IABConsent\Bits;

final class BitsTest extends TestCase
{
	/**
	 * @throws ReflectionException
	 */
	public function testEncodeIntToBits()
	{
		// encodes an integer to a bit string
		$object = new Bits();
		$reflector = new ReflectionClass('IABConsent\Bits');
		$method = $reflector->getMethod('encodeIntToBits');
		$method->setAccessible(true);
		$result = $method->invokeArgs($object, [123]);

		$this->assertEquals('1111011', $result);

		// encodes an integer to a bit string with padding
		$result = $method->invokeArgs($object, [123, 12]);
		$this->assertEquals('000001111011', $result);
	}

	/**
	 * @throws ReflectionException
	 */
	public function testEncodeBoolToBits()
	{
		// encodes a "true" boolean to a bit string
		$object = new Bits();
		$reflector = new ReflectionClass('IABConsent\Bits');
		$method = $reflector->getMethod('encodeBoolToBits');
		$method->setAccessible(true);
		$result = $method->invokeArgs($object, [true]);
		$this->assertEquals('1', $result);
		// encode a "false" boolean to a bit string
		$result = $method->invokeArgs($object, [false]);
		$this->assertEquals('0', $result);
	}

	/**
	 * @throws Exception
	 */
	public function testEncodeDateToBits()
	{
		$aDate = new \DateTime();
		$aDate->setTimestamp(1512661975);
		$object = new Bits();
		$reflector = new ReflectionClass('IABConsent\Bits');
		$method = $reflector->getMethod('encodeDateToBits');
		$method->setAccessible(true);
		// encode a date to a bit string
		$result = $method->invokeArgs($object, [$aDate]);
		$this->assertEquals('1110000101100111011110011001100110', $result);
		// encode a date to a bit string with padding
		$result = $method->invokeArgs($object, [$aDate, 36]);
		$this->assertEquals('001110000101100111011110011001100110', $result);
	}

	/**
	 * @throws ReflectionException
	 */
	public function testEncodeLetterToBits()
	{
		$object = new Bits();
		$reflector = new ReflectionClass('IABConsent\Bits');
		$method = $reflector->getMethod('encodeLetterToBits');
		$method->setAccessible(true);
		// encodes a letter to a bit string
		$this->assertEquals('0', $method->invokeArgs($object, ['a']));
		$this->assertEquals('1010', $method->invokeArgs($object, ['K']));
		$this->assertEquals('11001', $method->invokeArgs($object, ['z']));
		// encodes a letter to a bit string with padding
		$this->assertEquals('000000', $method->invokeArgs($object, ['a', 6]));
		$this->assertEquals('001010', $method->invokeArgs($object, ['K', 6]));
		$this->assertEquals('011001', $method->invokeArgs($object, ['z', 6]));
	}

	/**
	 * @throws ReflectionException
	 */
	public function testEncodeLanguageToBits()
	{
		$object = new Bits();
		$reflector = new ReflectionClass('IABConsent\Bits');
		$method = $reflector->getMethod('encodeLanguageToBits');
		$method->setAccessible(true);
		// encodes a language code to a bit string
		$this->assertEquals('000100001101', $method->invokeArgs($object, ['en', 12]));
		$this->assertEquals('000100001101', $method->invokeArgs($object, ['EN', 12]));
		$this->assertEquals('000101010001', $method->invokeArgs($object, ['fr', 12]));
		$this->assertEquals('000101010001', $method->invokeArgs($object, ['FR', 12]));
	}

	/**
	 * @throws ReflectionException
	 */
	public function testDecodeBitsToInt()
	{
		// decodes a bit string to original encoded value
		$object = new Bits();
		$reflector = new ReflectionClass('IABConsent\Bits');
		$methodDecode = $reflector->getMethod('decodeBitsToInt');
		$methodDecode->setAccessible(true);
		$methodEncode = $reflector->getMethod('encodeIntToBits');
		$methodEncode->setAccessible(true);

		$bitString = $methodEncode->invokeArgs($object, [123]);
		$decoded = $methodDecode->invokeArgs($object, [$bitString, 0, strlen($bitString)]);

		$this->assertEquals(123, $decoded);
	}

	/**
	 * @throws Exception
	 */
	public function testDecodeBitsToDate()
	{
		// decodes a bit string to original encoded value
		$object = new Bits();
		$reflector = new ReflectionClass('IABConsent\Bits');
		$methodDecode = $reflector->getMethod('decodeBitsToDate');
		$methodDecode->setAccessible(true);
		$methodEncode = $reflector->getMethod('encodeDateToBits');
		$methodEncode->setAccessible(true);
		$aDate = new DateTime('2018-07-15');
		$bitString = $methodEncode->invokeArgs($object, [$aDate]);
		$decoded = $methodDecode->invokeArgs($object, [$bitString, 0, strlen($bitString)]);

		$this->assertEquals($aDate->getTimestamp(), $decoded->getTimestamp());
	}

	/**
	 * @throws ReflectionException
	 */
	public function testDecodeBitsToBool()
	{
		$object = new Bits();
		$reflector = new ReflectionClass('IABConsent\Bits');
		$methodDecode = $reflector->getMethod('decodeBitsToBool');
		$methodDecode->setAccessible(true);
		$methodEncode = $reflector->getMethod('encodeBoolToBits');
		$methodEncode->setAccessible(true);
		// decodes a bit string to original encoded "true" value
		$bitString = $methodEncode->invokeArgs($object, [true]);
		$decoded = $methodDecode->invokeArgs($object, [$bitString, 0, strlen($bitString)]);
		$this->assertEquals(true, $decoded);
		// decodes a bit string to original encoded "false" value
		$bitString = $methodEncode->invokeArgs($object, [false]);
		$decoded = $methodDecode->invokeArgs($object, [$bitString, 0, strlen($bitString)]);
		$this->assertEquals(false, $decoded);
	}

	/**
	 * @throws ReflectionException
	 */
	public function testDecodeBitsToLetter()
	{
		$object = new Bits();
		$reflector = new ReflectionClass('IABConsent\Bits');
		$methodDecode = $reflector->getMethod('decodeBitsToLetter');
		$methodDecode->setAccessible(true);
		$methodEncode = $reflector->getMethod('encodeLetterToBits');
		$methodEncode->setAccessible(true);
		// decodes a bit string to a letter
		$this->assertEquals('a', $methodDecode->invokeArgs($object, ['000000']));
		$this->assertEquals('k', $methodDecode->invokeArgs($object, ['001010']));
		$this->assertEquals('z', $methodDecode->invokeArgs($object, ['011001']));
		// decodes a bit string to its original value
		$bitString = $methodEncode->invokeArgs($object, ['z', 6]);
		$decoded = $methodDecode->invokeArgs($object, [$bitString]);
		$this->assertEquals('z', $decoded);
	}

	/**
	 * @throws ReflectionException
	 */
	public function testDecodeBitsToLanguage()
	{
		$object = new Bits();
		$reflector = new ReflectionClass('IABConsent\Bits');
		$methodDecode = $reflector->getMethod('decodeBitsToLanguage');
		$methodDecode->setAccessible(true);
		$methodEncode = $reflector->getMethod('encodeLanguageToBits');
		$methodEncode->setAccessible(true);
		// decodes a bit string to a language code
		$this->assertEquals('en', $methodDecode->invokeArgs($object, ['000100001101', 0, 12]));
		$this->assertEquals('fr', $methodDecode->invokeArgs($object, ['000101010001', 0, 12]));
		// decodes a bit string to its original value
		$bitString = $methodEncode->invokeArgs($object, ['en', 12]);
		$decoded = $methodDecode->invokeArgs($object, [$bitString, 0, 12]);
		$this->assertEquals('en', $decoded);
	}

	/**
	 * @throws Exception
	 */
	public function testEncodeToBase64NonExistentVersion()
	{
		$this->expectException('Exception');
		$aDate = new DateTime('2018-07-15 07:00:00');
		// fails to encode a version that does not exist
		$consentData = [
			'version' => 999,
			'created' => $aDate,
			'lastUpdated' => $aDate,
			'cmpId' => 1,
			'vendorListVersion' => 1,
		];
		Bits::encodeToBase64($consentData);
	}

	/**
	 * @throws Exception
	 */
	public function testEncodeToBase64InvalidVersion()
	{
		$this->expectException('Exception');
		$aDate = new DateTime('2018-07-15 07:00:00');

		// fails to encode an invalid version
		$consentData = [
			'version' => 'hello',
			'created' => $aDate,
			'lastUpdated' => $aDate,
			'cmpId' => 1,
			'vendorListVersion' => 1,
		];

		Bits::encodeToBase64($consentData);
	}

	/**
	 * @throws Exception
	 */
	public function testDecodeFromBase64InvalidVersion()
	{
		$this->expectException('Exception');
		$object = new Bits();
		$reflector = new ReflectionClass('IABConsent\Bits');
		$method = $reflector->getMethod('encodeIntToBits');
		$method->setAccessible(true);

		$bitString = $method->invokeArgs($object, [999, 6]);

		Bits::decodeFromBase64($bitString);
	}

	/**
	 * @throws Exception
	 */
	public function testEncodeDecodeRanges()
	{
		// encodes and decodes the vendor value with ranges back to original value
		$aDate = new DateTime('2018-07-15 07:00:00');
		$consentData = [
			'version' => 1,
			'created' => $aDate,
			'lastUpdated' => $aDate,
			'cmpId' => 1,
			'cmpVersion' => 2,
			'consentScreen' => 3,
			'consentLanguage' => 'en',
			'vendorListVersion' => 1,
			'purposeIdBitString' => '111000001010101010001101',
			'maxVendorId' => 5,
			'isRange' => true,
			'defaultConsent' => false,
			'numEntries' => 2,
			'vendorRangeList' => [
				[
					'isRange' => true,
					'startVendorId' => 2,
					'endVendorId' => 4,
				],
				[
					'isRange' => false,
					'startVendorId' => 1,
				],
			],
		];
		$bitString = Bits::encodeToBase64($consentData);
		$decoded = Bits::decodeFromBase64($bitString);

		$this->assertEquals($consentData, $decoded);

		// encodes and decodes the vendor value with range ranges back to original value
		$consentData = [
			'version' => 1,
			'created' => $aDate,
			'lastUpdated' => $aDate,
			'cmpId' => 1,
			'cmpVersion' => 2,
			'consentScreen' => 3,
			'consentLanguage' => 'en',
			'vendorListVersion' => 1,
			'purposeIdBitString' => '111000001010101010001101',
			'maxVendorId' => 5,
			'isRange' => true,
			'defaultConsent' => false,
			'numEntries' => 2,
			'vendorRangeList' => [
				[
					'isRange' => false,
					'startVendorId' => 2,
				],
				[
					'isRange' => false,
					'startVendorId' => 1,
				],
			],
		];
		$bitString = Bits::encodeToBase64($consentData);
		$decoded = Bits::decodeFromBase64($bitString);

		$this->assertEquals($consentData, $decoded);
		// encodes and decodes the vendor value without ranges back to original value
		$consentData = [
			'version' => 1,
			'created' => $aDate,
			'lastUpdated' => $aDate,
			'cmpId' => 1,
			'cmpVersion' => 2,
			'consentScreen' => 3,
			'consentLanguage' => 'en',
			'vendorListVersion' => 1,
			'purposeIdBitString' => '000000001010101010001100',
			'maxVendorId' => 5,
			'isRange' => false,
			'vendorIdBitString' => '10011',
		];
		$bitString = Bits::encodeToBase64($consentData);
		$decoded = Bits::decodeFromBase64($bitString);

		$this->assertEquals($consentData, $decoded);

	}
}