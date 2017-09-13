<?php
require 'vendor/autoload.php';
require 'functions/commons.php';

// Get the user's IP address
$ip_address = get_ip_address();

// Database classes
$database = (new ZenApp\ZenAppConnection)->connect();
$ip_address_pixel = new ZenApp\ZenAppPagePixel($database, $ip_address);

// Create or update the pixel as needed
$ip_address_pixel->ip_address_key_pixel();