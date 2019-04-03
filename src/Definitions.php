<?php

namespace IABConsent;

class Definitions {
	public static function getVersionNumBits() {
		return 6;
	}

	public static function getVendorVersionMap() {
		return [
			1 =>
				[
					'version' => 1,
					'metadataFields' =>
						[
							0 => 'version',
							1 => 'created',
							2 => 'lastUpdated',
							3 => 'cmpId',
							4 => 'cmpVersion',
							5 => 'consentScreen',
							6 => 'vendorListVersion',
						],
					'fields' =>
						[
							0 =>
								[
									'name' => 'version',
									'type' => 'int',
									'numBits' => 6,
								],
							1 =>
								[
									'name' => 'created',
									'type' => 'date',
									'numBits' => 36,
								],
							2 =>
								[
									'name' => 'lastUpdated',
									'type' => 'date',
									'numBits' => 36,
								],
							3 =>
								[
									'name' => 'cmpId',
									'type' => 'int',
									'numBits' => 12,
								],
							4 =>
								[
									'name' => 'cmpVersion',
									'type' => 'int',
									'numBits' => 12,
								],
							5 =>
								[
									'name' => 'consentScreen',
									'type' => 'int',
									'numBits' => 6,
								],
							6 =>
								[
									'name' => 'consentLanguage',
									'type' => 'language',
									'numBits' => 12,
								],
							7 =>
								[
									'name' => 'vendorListVersion',
									'type' => 'int',
									'numBits' => 12,
								],
							8 =>
								[
									'name' => 'purposeIdBitString',
									'type' => 'bits',
									'numBits' => 24,
								],
							9 =>
								[
									'name' => 'maxVendorId',
									'type' => 'int',
									'numBits' => 16,
								],
							10 =>
								[
									'name' => 'isRange',
									'type' => 'bool',
									'numBits' => 1,
								],
							11 =>
								[
									'name' => 'vendorIdBitString',
									'type' => 'bits',
									'numBits' => function ($obj) {
										return $obj["maxVendorId"];
									},
									'validator' => function ($obj) {
										return ! $obj["isRange"];
									},
								],
							12 =>
								[
									'name' => 'defaultConsent',
									'type' => 'bool',
									'numBits' => 1,
									'validator' => function ($obj) {
										return $obj["isRange"];
									},
								],
							13 =>
								[
									'name' => 'numEntries',
									'numBits' => 12,
									'type' => 'int',
									'validator' => function ($obj) {
										return $obj["isRange"];
									},
								],
							14 =>
								[
									'name' => 'vendorRangeList',
									'type' => 'list',
									'validator' => function ($obj) {
										return $obj["isRange"];
									},
									'listCount' => function ($obj) {
										return $obj["numEntries"];
									},
									'fields' =>
										[
											0 =>
												[
													'name' => 'isRange',
													'type' => 'bool',
													'numBits' => 1,
												],
											1 =>
												[
													'name' => 'startVendorId',
													'type' => 'int',
													'numBits' => 16,
												],
											2 =>
												[
													'name' => 'endVendorId',
													'type' => 'int',
													'numBits' => 16,
													'validator' => function ($obj) {
														return $obj["isRange"];
													},
												],
										],
								],
						],
				],
		];
	}
}