<?php

use PHPUnit\Framework\TestCase;
use IABConsent\ConsentString;

final class ConsentStringTest extends TestCase
{
	private $vendorList;
	private $consentData;

	const RESULT_STRING = "BOQ6ZEAOQ6ZEAABACDENAOwAAAAHCACgACAAQABA";
	const METADATA_STRING = "BOQ6ZEAOQ6ZEAABACD__ABAAAAAHCAAA";

	private function initializeVendorList() {
		$this->vendorList = json_decode(file_get_contents(dirname(__FILE__) . '/vendors.json'), true);
	}

	/**
	 * @throws Exception
	 */
	private function initializeConsentData() {
		$aDate = new DateTime('2018-07-15');

		$this->consentData = [
			'version' => 1,
			'cmpId' => 1,
			'cmpVersion' => 2,
			'consentScreen' => 3,
			'consentLanguage' => 'en',
			'vendorListVersion' => 1,
			'maxVendorId' => max(array_column($this->vendorList['vendors'], 'id')),
			'created' => $aDate,
			'lastUpdated' => $aDate,
			'allowedPurposeIds' => [1,2],
			'allowedVendorIds' => [1,2,4],
		];
	}

	/**
	 * @throws Exception
	 */
	public function testConsentString() {
		// gives the same result when encoding then decoding data
		$this->initializeVendorList();
		$this->initializeConsentData();

		$cs = new ConsentString();
		foreach ($this->consentData as $property => $value) {
			$cs->{$property} = $value;
		}
		$cs->setGlobalVendorList($this->vendorList);
		$this->assertEquals(self::RESULT_STRING, $cs->getConsentString(false));

		$cs2 = new ConsentString(self::RESULT_STRING);
		$cs2->setGlobalVendorList($this->vendorList);
		$this->assertEquals($cs, $cs2);
	}

	/**
	 * @throws Exception
	 */
	public function testGetMaxVendorId() {
		// gets the max vendor id as expected
		$this->initializeVendorList();
		$cs = new ConsentString();
		$cs->setGlobalVendorList($this->vendorList);
		$this->assertEquals(112, $cs->getMaxVendorId());
	}

	/**
	 * @throws Exception
	 */
	public function testGetParsedVendorConsents() {
		// gets the parsed vendor consents
		$this->initializeVendorList();
		$cs = new ConsentString(self::RESULT_STRING);
		$cs->setGlobalVendorList($this->vendorList);
		$parsedVendors = $cs->getParsedVendorConsents();
		$this->assertEquals(112, strlen($parsedVendors));
		for ($i = 0; $i < $cs->getMaxVendorId(); $i++) {
			if (array_search($i + 1, $cs->allowedVendorIds) !== false) {
				$this->assertEquals('1', $parsedVendors[$i]);
			} else {
				$this->assertEquals('0', $parsedVendors[$i]);
			}
		}
	}

	/**
	 * @throws Exception
	 */
	public function testGetParsedPurposeConsents() {
		// gets the parsed purpose consents
		$this->initializeVendorList();
		$cs = new ConsentString(self::RESULT_STRING);
		$cs->setGlobalVendorList($this->vendorList);
		$parsedPurposes = $cs->getParsedPurposeConsents();
		$this->assertEquals(count($this->vendorList['purposes']), strlen($parsedPurposes));
		for ($i = 0; $i < count($this->vendorList['purposes']); $i++) {
			if (array_search($i + 1, $cs->allowedPurposeIds) !== false) {
				$this->assertEquals('1', $parsedPurposes[$i]);
			} else {
				$this->assertEquals('0', $parsedPurposes[$i]);
			}
		}
	}

	/**
	 * @throws Exception
	 */
	public function testGetMetadataString() {
		// encodes the Metadata String as expected
		$this->initializeVendorList();
		$this->initializeConsentData();
		$cs = new ConsentString();
		$cs->setGlobalVendorList($this->vendorList);
		foreach ($this->consentData as $property => $value) {
			$cs->{$property} = $value;
		}
		$this->assertEquals(self::METADATA_STRING, $cs->getMetadataString());
	}

	/**
	 * @throws Exception
	 */
	public function testDecodeMetadataString() {
		// decodes the Metadata String as expected
		$this->initializeVendorList();
		$this->initializeConsentData();
		$cs = new ConsentString();
		$result = $cs->decodeMetadataString(self::METADATA_STRING);

		$this->assertEquals($this->consentData['cmpId'], $result['cmpId']);
		$this->assertEquals($this->consentData['cmpVersion'], $result['cmpVersion']);
		$this->assertEquals($this->consentData['version'], $result['version']);
		$this->assertEquals($this->consentData['vendorListVersion'], $result['vendorListVersion']);
		$this->assertEquals($this->consentData['created'], $result['created']);
		$this->assertEquals($this->consentData['lastUpdated'], $result['lastUpdated']);
		$this->assertEquals($this->consentData['consentScreen'], $result['consentScreen']);
	}

	/**
	 * @throws Exception
	 */
	public function testSetVendorAllowed() {
		// can manipulate one vendor permission without affecting the others
		$this->initializeVendorList();
		$cs = new ConsentString(self::RESULT_STRING);
		$cs->setGlobalVendorList($this->vendorList);
		$allowedVendorsBefore = $cs->allowedVendorIds;
		$cs->setVendorAllowed(2, false);
		$this->assertEquals(count($allowedVendorsBefore) - 1, count($cs->allowedVendorIds));
	}

	/**
	 * @throws Exception
	 */
	public function testSetPurposeAllowed() {
		// can manipulate one purpose permission without affecting the others
		$this->initializeVendorList();
		$cs = new ConsentString(self::RESULT_STRING);
		$cs->setGlobalVendorList($this->vendorList);
		$allowedPurposes = $cs->allowedPurposeIds;
		$cs->setPurposeAllowed(1, false);
		$this->assertEquals(count($allowedPurposes) - 1, count($cs->allowedPurposeIds));

		$cs->setPurposeAllowed(1, true);
		$this->assertEquals(true, $cs->isPurposeAllowed(1));
	}

	/**
	 * @throws Exception
	 */
	public function testSetGlobalVendorListExceptions() {
		// throws an error if the provided vendor list does not respect the IAB format
		$this->expectException('Exception');
		(new ConsentString())->setGlobalVendorList([]);
		(new ConsentString())->setGlobalVendorList([
			'vendorListVersion' => 1,
		]);
		(new ConsentString())->setGlobalVendorList([
			'vendorListVersion' => 1,
			'purposes' => [],
		]);
		(new ConsentString())->setGlobalVendorList([
			'vendorListVersion' => 1,
			'vendors' => [],
		]);
		(new ConsentString())->setGlobalVendorList([
			'version' => 1,
			'purposes' => [],
			'vendors' => [],
		]);
	}

	/**
	 * @throws Exception
	 */
	public function testSetGlobalVendorList() {
		// does not throw an error if the provided vendor list does respect the IAB format
		$consent = new ConsentString();
		$consent->setGlobalVendorList([
			'vendorListVersion' => 1,
			'purposes' => [],
			'vendors' => [],
		]);

		$this->assertEquals(1, $consent->vendorListVersion);
	}

	/**
	 * @throws Exception
	 */
	public function testGlobalListSorted() {
		// sorts the vendor list by ID
		$consent = new ConsentString();
		$consent->setGlobalVendorList([
			'vendorListVersion' => 1,
			'purposes' => [],
			'vendors' => [
				['id' => 2 ],
				['id' => 1 ],
			],
		]);

		$this->assertSame([
			['id' => 1 ],
			['id' => 2 ],
		], $consent->getGlobalVendorList()['vendors']);
	}
}