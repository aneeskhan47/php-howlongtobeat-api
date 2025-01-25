<p align="center">
    <img src="https://banners.beyondco.de/HowLongToBeat%20PHP%20API.png?theme=light&packageManager=composer+require&packageName=aneeskhan47%2Fphp-howlongtobeat-api&pattern=architect&style=style_1&description=A+PHP+API+wrapper+for+HowLongToBeat.com&md=1&showWatermark=0&fontSize=100px&images=https%3A%2F%2Fwww.php.net%2Fimages%2Flogos%2Fnew-php-logo.svg" height="300" alt="PHP HowLongToBeat API">
    <p align="center">
        <a href="https://packagist.org/packages/aneeskhan47/php-howlongtobeat-api"><img alt="Total Downloads" src="https://img.shields.io/packagist/dt/aneeskhan47/php-howlongtobeat-api"></a>
        <a href="https://packagist.org/packages/aneeskhan47/php-howlongtobeat-api"><img alt="Latest Version" src="https://img.shields.io/packagist/v/aneeskhan47/php-howlongtobeat-api"></a>
        <a href="https://packagist.org/packages/aneeskhan47/php-howlongtobeat-api"><img alt="License" src="https://img.shields.io/packagist/l/aneeskhan47/php-howlongtobeat-api"></a>
    </p>
</p>

A simple PHP API to read data from [HowLongToBeat.com](https://howlongtobeat.com).

It is inspired by [ScrappyCocco - HowLongToBeat-PythonAPI](https://github.com/ScrappyCocco/HowLongToBeat-PythonAPI) Python API.

------

## ⚡️ Installation

> **Requires PHP 7.0 or higher**

```bash
composer require aneeskhan47/php-howlongtobeat-api
```

------

## 🚀 Usage

#### Search for games by title

by default, it will return 25 results per page and the first page.

```php
require 'vendor/autoload.php';

use AneesKhan47\HowLongToBeat\HowLongToBeat;

$hltb = new HowLongToBeat();

try {
    $results = $hltb->searchByTitle("The Last of Us");

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
```

You can also specify the number of results per page. for example, to get second page with 10 results per page, you can do this:

```php
$results = $hltb->searchByTitle("The Last of Us", 2, 10);
```

Note: The maximum number of results per allowed is 25. This is by HowLongToBeat.com limitation.

#### Search for a single game by its ID

```php
$game = $hltb->searchById(26286);

echo "Game: " . $game->name . "\n";
echo "Image: " . $game->image_url . "\n";
echo "Main Story: " . $game->main_story_time . "\n";
echo "Main + Extra: " . $game->main_extra_time . "\n";
echo "Completionist: " . $game->completionist_time . "\n";
echo "------------------------\n";
```

------

## 🔒 Security

If you discover any security-related issues, please email kingkhan2388@gmail.com instead of using the issue tracker.

------

## 🙌 Credits

- [Anees Khan](https://github.com/aneeskhan47)
- [All Contributors](../../contributors)

------

## 📜 License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.