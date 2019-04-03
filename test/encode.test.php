<?php

require_once(dirname(__FILE__) . '/../src/Encoder.php');
require_once(dirname(__FILE__) . '/../src/Decoder.php');
$vendorList = json_decode(file_get_contents(dirname(__FILE__) . '/vendors.json'), true);
/**
var_dump(Encoder::convertVendorsToRanges(
	[
		['id' => 1 ],
		['id' => 2 ],
		['id' => 3 ],
		['id' => 4 ],
		['id' => 5 ],
	],
	[1,2,3,4,5]
));
*/
/**
var_dump(Encoder::convertVendorsToRanges(
	[
		['id' => 1 ],
		['id' => 2 ],
		['id' => 3 ],
		['id' => 4 ],
		['id' => 5 ],
	],
	[1,2,3,5]
));
*/
/**
var_dump(Encoder::convertVendorsToRanges(
	[
		['id' => 1 ],
		['id' => 2 ],
		['id' => 3 ],
		['id' => 7 ],
	],
	[1,2,3,7]
));
*/
/**
var_dump(Encoder::convertVendorsToRanges(
	[
		['id' => 1 ],
		['id' => 3 ],
		['id' => 7 ],
	],
	[1,3,7]
));
*/

$aDate = new DateTime('2018-07-15');

$consentData = [
	'version' => 1,
	'cmpId' => 1,
	'cmpVersion' => 2,
	'consentScreen' => 3,
	'consentLanguage' => 'en',
	'created' => $aDate,
	'lastUpdated' => $aDate,
	'allowedPurposeIds' => [1,2],
	'allowedVendorIds' => [1,2,4],
	'vendorList' => $vendorList,
	'vendorListVersion' => $vendorList['vendorListVersion'],
];
$res = Encoder::encodeConsentString($consentData);
var_dump($res);
$lala = Decoder::decodeConsentString($res);
var_dump($lala);

