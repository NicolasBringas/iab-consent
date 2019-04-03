<?php

require_once(dirname(__FILE__) . '/../src/ConsentString.php');

$cs = new \IABConsent\ConsentString("BOQ6ZEAOQ6ZEAABACDENAOwAAAAHCADAACAAQAAQ");

var_dump($cs->getVendorsAllowed());