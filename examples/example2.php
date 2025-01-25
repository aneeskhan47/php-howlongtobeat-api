<?php

require 'vendor/autoload.php';

use AneesKhan47\HowLongToBeat\HowLongToBeat;

$hltb = new HowLongToBeat();

try {
    $results = $hltb->searchById(10270);

    echo "Game: " . $results->name . "\n";
    echo "Image: " . $results->image_url . "\n";
    echo "Main Story: " . $results->main_story_time . "\n";
    echo "Main + Extra: " . $results->main_extra_time . "\n";
    echo "Completionist: " . $results->completionist_time . "\n";
    echo "------------------------\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
