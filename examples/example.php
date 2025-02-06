<?php

require 'vendor/autoload.php';

use AneesKhan47\HowLongToBeat\HowLongToBeat;

$hltb = new HowLongToBeat();

try {
    $startTime = microtime(true);

    $results = $hltb->searchByTitle("The Last of Us");

    $endTime = microtime(true);
    $executionTime = ($endTime - $startTime);

    echo "API Request Time: " . number_format($executionTime, 2) . " seconds\n";
    echo "------------------------\n";

    foreach ($results->games as $game) {
        echo "Game: " . $game->name . "\n";
        echo "Image: " . $game->image_url . "\n";
        echo "Main Story: " . $game->main_story_time . "\n";
        echo "Main + Extra: " . $game->main_extra_time . "\n";
        echo "Completionist: " . $game->completionist_time . "\n";
        echo "------------------------\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
