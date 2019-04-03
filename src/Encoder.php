<?php

namespace IABConsent;

class Encoder
{
	/**
	 * Encode a list of vendor IDs into bits
	 *
	 * @param int $maxVendorId Highest vendor ID in the vendor list
	 * @param array $allowedVendorIds Vendors that the user has given consent to
	 * @return string
	 */
	public static function encodeVendorIdsToBits($maxVendorId, $allowedVendorIds = [])
	{
		if (is_null($allowedVendorIds)) {
			$allowedVendorIds = [];
		}
		$vendorString = "";
		for ($id = 1; $id <= $maxVendorId; $id++) {
			$vendorString .= (array_search($id, $allowedVendorIds) !== false) ? '1' : '0';
		}

		return Bits::padRight($vendorString, max([0, $maxVendorId - strlen($vendorString)]));
	}

	/**
	 * Encode a list of purpose IDs into bits
	 *
	 * @param array $purposes List of purposes from the vendor list
	 * @param array $allowedPurposeIds List of purpose IDs that the user has given consent to
	 * @return string
	 */
	public static function encodePurposeIdsToBits($purposes, $allowedPurposeIds = [])
	{
		if (is_null($allowedPurposeIds)) {
			$allowedPurposeIds = [];
		}
		$maxPurposeId = 0;
		for ($i = 0; $i < count($purposes); $i++) {
			$maxPurposeId = max([$maxPurposeId, $purposes[$i]['id']]);
		}
		for ($i = 0; $i < count($allowedPurposeIds); $i++) {
			$maxPurposeId = max([$maxPurposeId, $allowedPurposeIds[$i]]);
		}
		$purposeString = "";
		for ($id = 1; $id <= $maxPurposeId; $id++) {
			$purposeString .= (array_search($id, $allowedPurposeIds) !== false) ? '1' : '0';
		}

		return $purposeString;
	}

	/**
	 * Convert a list of vendor IDs to ranges
	 *
	 * @param array $vendors List of vendors from the vendor list (important: this list must to be sorted by ID)
	 * @param array $allowedVendorIds List of vendor IDs that the user has given consent to
	 * @return array
	 */
	public static function convertVendorsToRanges($vendors, $allowedVendorIds)
	{
		$range = $ranges = [];
		if (is_null($allowedVendorIds)) {
			$allowedVendorIds = [];
		}
		$idsInList = array_column($vendors, 'id');
		for ($index = 0; $index < count($vendors); $index++) {
			$id = $vendors[$index]['id'];
			if (array_search($id, $allowedVendorIds) !== false) {
				$range[] = $id;
			}
			// Do we need to close the current range?
			if (
				(
					array_search($id, $allowedVendorIds) === false // The vendor we are evaluating is not allowed
					|| $index === (count($vendors) - 1) // There is no more vendor to evaluate
					|| array_search($id + 1, $idsInList) === false // There is no vendor after this one (ie there is a gap in the vendor IDs) ; we need to stop here to avoid including vendors that do not have consent
				)
				&& count($range)
			) {
				$startVendorId = array_shift($range);
				$endVendorId = array_pop($range);
				$range = [];
				$ranges[] = [
					'isRange' => is_int($endVendorId),
					'startVendorId' => $startVendorId,
					'endVendorId' => $endVendorId,
				];
			}
		}

		return $ranges;
	}

	/**
	 * Get maxVendorId from the list of vendors and return that id
	 *
	 * @param array $vendors
	 * @return int
	 */
	public static function getMaxVendorId($vendors)
	{
		$maxVendorId = 0;
		foreach ($vendors as $vendor) {
			$vendor['id'] = (int)$vendor['id'];
			if ($vendor['id'] > $maxVendorId) {
				$maxVendorId = $vendor['id'];
			}
		}
		return $maxVendorId;
	}

	/**
	 * Encode consent data into a web-safe base64-encoded string
	 *
	 * @param array consentData Data to include in the string (see `Definitions.php` for the list of fields)
	 * @throws \Exception
	 * @return string
	 */
	public static function encodeConsentString($consentData)
	{
		$maxVendorId = $consentData['maxVendorId'];
		$allowedPurposeIds = $consentData['allowedPurposeIds'];
		$allowedVendorIds = $consentData['allowedVendorIds'];
		$vendorList = $consentData['vendorList'];
		$vendors = $vendorList['vendors'];
		$purposes = $vendorList['purposes'];

		// if no maxVendorId is in the ConsentData, get it
		if (! $maxVendorId) {
			$maxVendorId = self::getMaxVendorId($vendors);
		}
		// Encode the data with and without ranges and return the smallest encoded payload
		$noRangesData = Bits::encodeToBase64(array_merge($consentData, [
			'maxVendorId' => $maxVendorId,
			'purposeIdBitString' => self::encodePurposeIdsToBits($purposes, $allowedPurposeIds),
			'isRange' => false,
			'vendorIdBitString' => self::encodeVendorIdsToBits($maxVendorId, $allowedVendorIds),
		]));

		$vendorRangeList = self::convertVendorsToRanges($vendors, $allowedVendorIds);
		$rangesData = Bits::encodeToBase64(array_merge($consentData, [
			'maxVendorId' => $maxVendorId,
			'purposeIdBitString' => self::encodePurposeIdsToBits($purposes, $allowedPurposeIds),
			'isRange' => true,
			'defaultConsent' => false,
			'numEntries' => count($vendorRangeList),
			'vendorRangeList' => $vendorRangeList,
		]));

		return strlen($noRangesData) < strlen($rangesData) ? $noRangesData : $rangesData;
	}
}