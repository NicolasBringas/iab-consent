<?php
namespace IABConsent;

class ConsentString {

	const consentLanguageRegexp = '/^[a-z]{2}$/';

	private $created;
	private $lastUpdated;
	private $version = 1;
	private $vendorList = null;
	private $vendorListVersion = null;
	private $cmpId = null;
	private $cmpVersion = null;
	private $consentScreen = null;
	private $consentLanguage = null;
	private $allowedPurposeIds = [];
	private $allowedVendorIds = [];

	/**
	 * ConsentString constructor.
	 * @param $baseString
	 * @throws \Exception
	 */
	public function __construct($baseString = null) {
		if (! is_null($baseString)) {
			$params = Decoder::decodeConsentString($baseString);
			foreach ($params as $key => $value) {
				if (isset($this->{$key})) {
					$this->{$key} = $value;
				}
			}

			return;
		}
		$this->created = new \DateTime();
		$this->lastUpdated = new \DateTime();
	}

	/**
	 * @param bool $updateDate
	 * @return string
	 * @throws \Exception
	 */
	public function getConsentString($updateDate = true) {
		if (! $this->vendorList) {
			throw new \Exception('ConsentString - A vendor list is required to encode a consent string');
		}
		if ($updateDate === true) {
			$this->lastUpdated = new \DateTime();
		}

		return Encoder::encodeConsentString([
			'version' => $this->version,
			'vendorList' => $this->vendorList,
			'allowedPurposeIds' => $this->allowedPurposeIds,
			'allowedVendorIds' => $this->allowedVendorIds,
			'created' => $this->created,
			'lastUpdated' => $this->lastUpdated,
			'cmpId' => $this->cmpId,
			'cmpVersion' => $this->cmpVersion,
			'consentScreen' => $this->consentScreen,
			'consentLanguage' => $this->consentLanguage,
			'vendorListVersion' => $this->vendorListVersion,
		]);
	}

	/**
	 * @return \DateTime
	 */
	public function getLastUpdated() {
		return $this->lastUpdated;
	}

	/**
	 * @param null $date
	 * @throws \Exception
	 */
	public function setLastUpdated($date = null) {
		if ($date) {
			$this->lastUpdated = new \DateTime($date);
		} else {
			$this->lastUpdated = new \DateTime();
		}
	}

	/**
	 * @return \DateTime
	 */
	public function getCreated() {
		return $this->created;
	}

	/**
	 * @param null $date
	 * @throws \Exception
	 */
	public function setCreated($date = null) {
		if ($date) {
			$this->created = new \DateTime($date);
		} else {
			$this->created = new \DateTime();
		}
	}

	/**
	 * @return int
	 */
	public function getMaxVendorId() {
		return Encoder::getMaxVendorId($this->vendorList['vendors']);
	}

	/**
	 * @return string
	 */
	public function getParsedVendorConsents() {
		return Encoder::encodeVendorIdsToBits($this->getMaxVendorId(), $this->allowedVendorIds);
	}

	/**
	 * @return string
	 */
	public function getParsedPurposeConsents() {
		return Encoder::encodePurposeIdsToBits($this->vendorList['purposes'], $this->allowedPurposeIds);
	}

	/**
	 * @return string
	 * @throws \Exception
	 */
	public function getMetadataString() {
		return Encoder::encodeConsentString([
			'version' => $this->getVersion(),
			'created' => $this->created,
			'lastUpdated' => $this->lastUpdated,
			'cmpId' => $this->cmpId,
			'cmpVersion' => $this->cmpVersion,
			'consentScreen' => $this->consentScreen,
			'vendorListVersion' => $this->vendorListVersion,
		]);
	}

	/**
	 * @param $encodedMetaData
	 * @return array
	 * @throws \Exception
	 */
	public static function decodeMetadataString($encodedMetaData) {
		$decodedString = Decoder::decodeConsentString($encodedMetaData);
		$metadata = [];
		$versionMap = Definitions::getVendorVersionMap();
		foreach ($versionMap[$decodedString['version']]['metadataFields'] as $field) {
			$metadata[$field] = $decodedString[$field];
		}

		return $metadata;
	}

	/**
	 * @return int
	 */
	public function getVendorListVersion() {
		return $this->vendorListVersion;
	}

	/**
	 * @return int
	 */
	public function getVersion() {
		return $this->version;
	}

	/**
	 * @param $vendorList
	 * @throws \Exception
	 */
	public function setGlobalVendorList($vendorList) {
		if (! is_array($vendorList)) {
			throw new \Exception('ConsentString - You must provide an array when setting the global vendor list');
		}
		if (
			! $vendorList['vendorListVersion']
			|| ! is_array($vendorList['purposes'])
			|| ! is_array($vendorList['vendors'])
		) {
			// The provided vendor list does not look valid
			throw new \Exception('ConsentString - The provided vendor list does not respect the schema from the IAB EU’s GDPR Consent and Transparency Framework');
		}

		// Cloning the GVL
		// It's important as we might transform it and don't want to modify objects that we do not own
		$this->vendorList = [
			'vendorListVersion' => $vendorList['vendorListVersion'],
      		'lastUpdated' => $vendorList['lastUpdated'],
      		'purposes' => $vendorList['purposes'],
      		'features' => $vendorList['features'],
			'vendors' => $vendorList['vendors'],
    	];
		// sort the vendors by ID (it breaks our range generation algorithm if they are not sorted)
		usort($this->vendorList['vendors'], function($a, $b) {
			return $a["id"] < $b["id"] ? -1 : 1;
		});
    	$this->vendorListVersion = $vendorList['vendorListVersion'];
	}

	/**
	 * @return array
	 */
	public function getGlobalVendorList() {
		return $this->vendorList;
	}

	/**
	 * @param $id
	 */
	public function setCmpId($id) {
		$this->cmpId = $id;
	}

	/**
	 * @return int
	 */
	public function getCmpId() {
		return $this->cmpId;
	}

	/**
	 * @param $version
	 */
	public function setCmpVersion($version) {
		$this->cmpVersion = $version;
	}

	/**
	 * @return int
	 */
	public function getCmpVersion() {
    	return $this->cmpVersion;
  	}

	/**
	 * @param $screenId
	 */
	public function setConsentScreen($screenId) {
  		$this->consentScreen = $screenId;
  	}

	/**
	 * @return int
	 */
	public function getConsentScreen() {
    	return $this->consentScreen;
	}

	/**
	 * @param $language
	 * @throws \Exception
	 */
	public function setConsentLanguage($language) {
		if (preg_match(self::consentLanguageRegexp, $language) === false) {
			throw new \Exception('ConsentString - The consent language must be a two-letter ISO639-1 code (en, fr, de, etc.)');
		}
		$this->consentLanguage = $language;
	}

	/**
	 * @return null
	 */
	public function getConsentLanguage() {
    	return $this->consentLanguage;
	}

	/**
	 * @param $purposeIds
	 */
	public function setPurposesAllowed($purposeIds) {
		$this->allowedPurposeIds = $purposeIds;
	}

	/**
	 * @return array
	 */
	public function getPurposesAllowed() {
    	return $this->allowedPurposeIds;
  	}

	/**
	 * @param $purposeId
	 * @param $value
	 */
	public function setPurposeAllowed($purposeId, $value) {
    	$purposeIndex = array_search($purposeId, $this->allowedPurposeIds);
    	if ($value === true && $purposeIndex === false) {
			$this->allowedPurposeIds[] = $purposeId;
		} else if ($value === false && $purposeIndex !== false) {
    		unset($this->allowedPurposeIds[$purposeIndex]);
		}
  	}

	/**
	 * @param $purposeId
	 * @return bool
	 */
	public function isPurposeAllowed($purposeId) {
    	return array_search($purposeId, $this->allowedPurposeIds) !== false;
  	}

	/**
	 * @param $vendorIds
	 */
	public function setVendorsAllowed($vendorIds) {
		$this->allowedVendorIds = $vendorIds;
	}

	/**
	 * @return array
	 */
	public function getVendorsAllowed() {
    	return $this->allowedVendorIds;
	}

	/**
	 * @param $vendorId
	 * @param $value
	 */
	public function setVendorAllowed($vendorId, $value) {
		$vendorIndex = array_search($vendorId, $this->allowedVendorIds);
		if ($value === true && $vendorIndex === false) {
			$this->allowedVendorIds[] = $vendorId;
		} else if ($value === false && $vendorIndex !== false) {
			unset($this->allowedVendorIds[$vendorIndex]);
		}
	}

	/**
	 * @param $vendorId
	 * @return bool
	 */
	public function isVendorAllowed($vendorId) {
		return array_search($vendorId, $this->allowedVendorIds) !== false;
	}
}