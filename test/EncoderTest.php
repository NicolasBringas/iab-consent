<?php

use PHPUnit\Framework\TestCase;
use IABConsent\Encoder;

final class EncoderTest extends TestCase
{
	private $vendorList;

	private function initializeVendorList()
	{
		$this->vendorList = json_decode(file_get_contents(dirname(__FILE__) . '/vendors.json'), true);
	}

	public function testConvertVendorsToRanges()
	{
		// converts a list of vendors to a full range
		$res = Encoder::convertVendorsToRanges([
			['id' => 1],
			['id' => 2],
			['id' => 3],
			['id' => 4],
			['id' => 5],
		], [1, 2, 3, 4, 5]);

		$this->assertEquals([['isRange' => true, 'startVendorId' => 1, 'endVendorId' => 5]], $res);

		// converts a list of vendors to a multiple ranges as needed
		$res = Encoder::convertVendorsToRanges([
			['id' => 1],
			['id' => 2],
			['id' => 3],
			['id' => 4],
			['id' => 5],
		], [1, 2, 3, 5]);

		$this->assertEquals([
			['isRange' => true, 'startVendorId' => 1, 'endVendorId' => 3],
			['isRange' => false, 'startVendorId' => 5, 'endVendorId' => null],
		], $res);

		//ignores missing vendors when creating ranges
		$res = Encoder::convertVendorsToRanges([
			['id' => 1],
			['id' => 2],
			['id' => 3],
			['id' => 7],
		], [1, 2, 3, 7]);

		$this->assertEquals([
			['isRange' => true, 'startVendorId' => 1, 'endVendorId' => 3],
			['isRange' => false, 'startVendorId' => 7, 'endVendorId' => null],
		], $res);

		$res = Encoder::convertVendorsToRanges([
			['id' => 1],
			['id' => 3],
			['id' => 7],
		], [1, 3, 7]);

		$this->assertEquals([
			['isRange' => false, 'startVendorId' => 1, 'endVendorId' => null],
			['isRange' => false, 'startVendorId' => 3, 'endVendorId' => null],
			['isRange' => false, 'startVendorId' => 7, 'endVendorId' => null],
		], $res);
	}

	/**
	 * @throws Exception
	 */
	public function testEncodeConsentString()
	{
		// encodes the consent data into a base64-encoded string
		$this->initializeVendorList();
		$aDate = new DateTime('2018-07-15 07:00:00');
		$consentData = [
			'version' => 1,
			'cmpId' => 1,
			'cmpVersion' => 2,
			'consentScreen' => 3,
			'consentLanguage' => 'en',
			'created' => $aDate,
			'lastUpdated' => $aDate,
			'allowedPurposeIds' => [1, 2],
			'allowedVendorIds' => [1, 2, 4],
			'vendorList' => $this->vendorList,
			'vendorListVersion' => $this->vendorList['vendorListVersion'],
		];

		$encodedString = Encoder::encodeConsentString($consentData);

		$this->assertEquals('BOQ7WlgOQ7WlgABACDENAOwAAAAHCADAACAAQAAQ', $encodedString);
	}

	public function testGetMaxVendorId()
	{
		// gets the max vendor id from the vendorList[vendors]
		$this->initializeVendorList();
		$maxVendorId = Encoder::getMaxVendorId($this->vendorList['vendors']);
		$this->assertEquals(112, $maxVendorId);
	}

	public function testEncodeVendorIdsToBits()
	{
		$this->initializeVendorList();
		// encodes vendor id values to bits and turns on the one I tell it to
		$setBit = 5;
		$maxVendorId = Encoder::getMaxVendorId($this->vendorList['vendors']);
		$bitString = Encoder::encodeVendorIdsToBits($maxVendorId, [$setBit]);

		$this->assertEquals(112, strlen($bitString));

		for ($i = 0; $i < $maxVendorId; $i++) {
			if ($i === ($setBit - 1)) {
				$this->assertEquals('1', $bitString[$i]);
			} else {
				$this->assertEquals('0', $bitString[$i]);
			}
		}
		// encodes vendor id values to bits and turns on the two I tell it to
		$setBit1 = 5;
		$setBit2 = 9;
		$bitString = Encoder::encodeVendorIdsToBits($maxVendorId, [$setBit1, $setBit2]);
		$this->assertEquals(112, strlen($bitString));
		for ($i = 0; $i < $maxVendorId; $i++) {
			if ($i === ($setBit1 - 1) || ($i === ($setBit2 - 1))) {
				$this->assertEquals('1', $bitString[$i]);
			} else {
				$this->assertEquals('0', $bitString[$i]);
			}
		}
	}

	public function testEncodePurposeIdsToBits()
	{
		$this->initializeVendorList();
		// encodes purpose id values to bits and turns on the one I tell it to
		$setBit = 4;
		$purposes = $this->vendorList['purposes'];
		$bitString = Encoder::encodePurposeIdsToBits($purposes, [$setBit]);
		$this->assertEquals(count($purposes), strlen($bitString));
		for ($i = 0; $i < count($purposes); $i++) {
			if ($i === ($setBit - 1)) {
				$this->assertEquals('1', $bitString[$i]);
			} else {
				$this->assertEquals('0', $bitString[$i]);
			}
		}
		// encodes purpose id values to bits and turns on the two I tell it to
		$setBit1 = 2;
		$setBit2 = 4;
		$bitString = Encoder::encodePurposeIdsToBits($purposes, [$setBit1, $setBit2]);
		$this->assertEquals(count($purposes), strlen($bitString));
		for ($i = 0; $i < count($purposes); $i++) {
			if ($i === ($setBit1 - 1) || ($i === ($setBit2 - 1))) {
				$this->assertEquals('1', $bitString[$i]);
			} else {
				$this->assertEquals('0', $bitString[$i]);
			}
		}
	}
}