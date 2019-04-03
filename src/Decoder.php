<?php

namespace IABConsent;

class Decoder
{
	/**
	 * @param $consentString
	 * @return array
	 * @throws \Exception
	 */
	public static function decodeConsentString($consentString)
	{
		$decoded = Bits::decodeFromBase64($consentString);
		$decoded['allowedPurposeIds'] = Bits::decodeBitsToIds($decoded['purposeIdBitString']);

		if ($decoded['isRange']) {
			$reduce = function ($acc, $el) {
				$lastVendorId = $el['isRange'] ? $el['endVendorId'] : $el['startVendorId'];
				for ($i = $el['startVendorId']; $i <= $lastVendorId; $i++) {
					$acc[$i] = true;
				}
				return $acc;
			};
			$idMap = array_reduce($decoded['vendorRangeList'], $reduce, []);
			$decoded['allowedVendorIds'] = [];
			for ($i = 1; $i <= $decoded['maxVendorId']; $i++) {
				if (
					(
						$decoded['defaultConsent'] && ! $idMap[$i] ||
						! $decoded['defaultConsent'] && $idMap[$i]
					) && array_search($i, $decoded['allowedVendorIds']) === false
				) {
					$decoded['allowedVendorIds'][] = $i;
				}

			}
		} else {
			$decoded['allowedVendorIds'] = Bits::decodeBitsToIds($decoded['vendorIdBitString']);
		}

		return [
			'version' => $decoded['version'],
			'cmpId' => $decoded['cmpId'],
			'cmpVersion' => $decoded['cmpVersion'],
			'consentScreen' => $decoded['consentScreen'],
			'consentLanguage' => $decoded['consentLanguage'],
			'vendorListVersion' => $decoded['vendorListVersion'],
			'maxVendorId' => $decoded['maxVendorId'],
			'created' => $decoded['created'],
			'lastUpdated' => $decoded['lastUpdated'],
			'allowedPurposeIds' => $decoded['allowedPurposeIds'],
			'allowedVendorIds' => $decoded['allowedVendorIds'],
		];
	}
}