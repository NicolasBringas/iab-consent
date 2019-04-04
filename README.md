# PHP Support for GDPR

This project includes a PHP Library for working with the IAB's [GDPR Transparency & Consent Framework](https://github.com/InteractiveAdvertisingBureau/GDPR-Transparency-and-Consent-Framework/blob/master/Consent%20string%20and%20vendor%20list%20formats%20v1.1%20Final.md).

## Installation with composer

```
composer require nicolasbringas/iab-consent
```

## Usage

### Decode a Consent String

```
$cs = new IABConsent\ConsentString("BOQ6ZEAOQ6ZEAABACDENAOwAAAAHCADAACAAQAAQ");

echo "Created Timestamp: " . $cs->getCreated()->getTimestamp() . "\n";
echo "Updated Timestamp: " . $cs->getLastUpdated()->getTimestamp() . "\n";
echo "Version: " . $cs->getVersion() . "\n";
echo "CMP Id: " . $cs->getCmpId() . "\n";
echo "CMP Version: " . $cs->getCmpVersion() . "\n";
echo "Consent Screen: " . $cs->getConsentScreen() . "\n";
echo "Consent Language: " . $cs->getConsentLanguage() . "\n";
echo "Vendor List Version: " . $cs->getVendorListVersion() . "\n";
echo "Allowed Purposes: " . implode(", ", $cs->getPurposesAllowed()) . "\n";
echo "Allowed Vendors: " . implode(", ", $cs->getVendorsAllowed()) . "\n";
echo "Purpose 1 is " . (($cs->isPurposeAllowed(1)) ? "Allowed" : "Not Allowed") . "\n";
echo "Purpose 3 is " . (($cs->isPurposeAllowed(3)) ? "Allowed" : "Not Allowed") . "\n";
echo "Vendor 1 is " . (($cs->isPurposeAllowed(1)) ? "Allowed" : "Not Allowed") . "\n";
echo "Vendor 3 is " . (($cs->isPurposeAllowed(3)) ? "Allowed" : "Not Allowed") . "\n";
```

### Outputs

```
Created Timestamp: 1531612800
Updated Timestamp: 1531612800
Version: 1
CMP Id: 1
CMP Version: 2
Consent Screen: 3
Consent Language: en
Vendor List Version: 14
Allowed Purposes: 1, 2
Allowed Vendors: 1, 2, 4
Purpose 1 is Allowed
Purpose 3 is Not Allowed
Vendor 1 is Allowed
Vendor 3 is Not Allowed
```

### Encode consent data

```
$cs = new IABConsent\ConsentString();

// Set the global vendor list
// You need to download and provide the vendor list yourself
// It can be found here - https://vendorlist.consensu.org/vendorlist.json
$cs->setGlobalVendorList($vendorList);

// Set the consent data
$cs->setCmpId(1);
$cs->setCmpVersion(1);
$cs->setConsentScreen(1);
$cs->setConsentLanguage('en');
$cs->setPurposesAllowed([1,2,4]);
$cs->setVendorsAllowed([1,24,245]);

// Encode the data into a web-safe base64 string
echo "Consent String: " . $cs->getConsentString() . "\n";
```
