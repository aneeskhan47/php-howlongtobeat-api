<?php

namespace AneesKhan47\HowLongToBeat;

use AneesKhan47\HowLongToBeat\Exceptions\HowLongToBeatException;
use AneesKhan47\HowLongToBeat\Models\Game;
use AneesKhan47\HowLongToBeat\Models\SearchResult;

class HowLongToBeat
{
    const BASE_URL = 'https://howlongtobeat.com/';
    private $headers;
    private $apiEndpoint;
    private $apiPath;

    /**
     * Create a new HowLongToBeat instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->headers = [
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36',
            'Content-Type: application/json',
            'Accept: */*',
            'Referer: https://howlongtobeat.com/'
        ];

        // Find both the API path and endpoint from the script
        $scriptData = $this->findApiData();
        $this->apiPath = $scriptData['path'];
        $this->apiEndpoint = $scriptData['endpoint'];
    }

    /**
     * Find the API path and endpoint from the main page script
     *
     * @return array
     */
    private function findApiData(): array
    {
        $html = $this->get(self::BASE_URL);

        // Find all script tags with _app- in their src
        if (!preg_match_all('/<script[^>]*src=["\']([^"\']*_app-[^"\']*\.js)["\'][^>]*>/i', $html, $matches)) {
            throw new HowLongToBeatException("Could not find app script");
        }

        // Get the first matching script
        $scriptUrl = self::BASE_URL . ltrim($matches[1][0], '/');
        $scriptContent = $this->get($scriptUrl);

        // Extract API endpoint (key)
        $apiEndpoint = $this->extractApiEndpoint($scriptContent);

        // Extract API path
        $apiPath = $this->extractApiPath($scriptContent, $apiEndpoint);

        return [
            'path' => $apiPath,
            'endpoint' => $apiEndpoint
        ];
    }

    /**
     * Extract the API endpoint (key) from the script content
     *
     * @param string $scriptContent
     * @return string
     */
    private function extractApiEndpoint(string $scriptContent): string
    {
        // Try to find the API key in the format: fetch("/api/s/".concat("X").concat("Y")
        if (preg_match('/fetch\(\s*["\']\/api\/\w+\/["\']((?:\.concat\(["\'][^"\']*["\']\))+)/', $scriptContent, $matches)) {
            // Extract the concatenated strings
            preg_match_all('/\.concat\(["\'](.*?)["\']\)/', $matches[1], $concatMatches);
            if (!empty($concatMatches[1])) {
                return implode('', $concatMatches[1]);
            }
        }

        // Fallback: try to find the API key in the users.id format
        if (preg_match('/users\s*:\s*{\s*id\s*:\s*["\']([^"\']+)["\']/', $scriptContent, $matches)) {
            return $matches[1];
        }

        throw new HowLongToBeatException("Could not find API endpoint");
    }

    /**
     * Extract the API path from the script content
     *
     * @param string $scriptContent
     * @param string $apiEndpoint
     * @return string
     */
    private function extractApiPath(string $scriptContent, string $apiEndpoint): string
    {
        // Try to find the pattern: fetch("/api/ouch/".concat("X").concat("Y"), ...
        if (preg_match_all('/fetch\(\s*["\'](\/api\/[^"\'\/]*)\/["\']((?:\s*\.concat\(\s*["\'](.*?)["\']\s*\))+)\s*,/', $scriptContent, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $path = $match[1];
                $concatCalls = $match[2];

                // Extract all concatenated strings
                preg_match_all('/\.concat\(\s*["\'](.*?)["\']\s*\)/', $concatCalls, $concatMatches);
                if (!empty($concatMatches[1])) {
                    $concatenatedStr = implode('', $concatMatches[1]);

                    // Check if the concatenated string matches the known API endpoint
                    if ($concatenatedStr === $apiEndpoint) {
                        return $path;
                    }
                }
            }
        }

        // Fallback to default path if we couldn't find it dynamically
        return '/api/s';
    }

    /**
     * Make a POST request to the API.
     *
     * @param string $url
     * @param array $data
     * @return array
     */
    private function post(string $url, array $data): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => $this->headers,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($response === false) {
            throw new HowLongToBeatException('cURL Error: ' . curl_error($ch));
        }

        curl_close($ch);

        if ($httpCode !== 200) {
            throw new HowLongToBeatException("HTTP Error: {$httpCode}");
        }

        return json_decode($response, true);
    }

    /**
     * Make a GET request to the API.
     *
     * @param string $url
     * @return string
     */
    private function get(string $url): string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $this->headers,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($response === false) {
            throw new HowLongToBeatException('cURL Error: ' . curl_error($ch));
        }

        curl_close($ch);

        if ($httpCode !== 200) {
            throw new HowLongToBeatException("HTTP Error: {$httpCode}");
        }

        return $response;
    }

    /**
     * Search for games by title
     *
     * @param string $query
     * @param int $page default 1
     * @param int $perPage default 25
     * @return \AneesKhan47\HowLongToBeat\Models\SearchResult
     * 
     * @throws \AneesKhan47\HowLongToBeat\Exceptions\HowLongToBeatException
     */
    public function searchByTitle(string $query, int $page = 1, int $perPage = 25): SearchResult
    {
        if ($perPage > 25) {
            throw new HowLongToBeatException("Maximum results per page is 25");
        }

        try {
            $data = $this->post(self::BASE_URL . ltrim($this->apiPath, '/') . '/' . $this->apiEndpoint, [
                'searchType' => 'games',
                'searchTerms' => explode(' ', $query),
                'searchPage' => $page,
                'size' => $perPage,
                'searchOptions' => [
                    'games' => [
                        'userId' => 0,
                        'platform' => '',
                        'sortCategory' => 'popular',
                        'rangeCategory' => 'main',
                        'rangeTime' => [
                            'min' => null,
                            'max' => null,
                        ],
                        'gameplay' => [
                            'perspective' => '',
                            'flow' => '',
                            'genre' => '',
                            'difficulty' => '',
                        ],
                        'rangeYear' => [
                            'min' => '',
                            'max' => '',
                        ],
                        'modifier' => '',
                    ],
                    'users' => [
                        'sortCategory' => 'postcount',
                    ],
                    'lists' => [
                        'sortCategory' => 'follows',
                    ],
                    'filter' => '',
                    'sort' => 0,
                    'randomizer' => 0,
                ],
                'useCache' => true,
            ]);

            $games = array_map(function ($gameData) {
                return new Game([
                    'id' => $gameData['game_id'] ?? 0,
                    'name' => $gameData['game_name'] ?? '',
                    'image_url' => $gameData['game_image'] ? "https://howlongtobeat.com/games/" . $gameData['game_image'] : null,
                    'main_story_time' => $gameData['comp_main'] ?? null,
                    'main_extra_time' => $gameData['comp_plus'] ?? null,
                    'completionist_time' => $gameData['comp_100'] ?? null,
                    'all_styles_time' => $gameData['comp_all'] ?? null,
                ]);
            }, $data['data'] ?? []);

            return new SearchResult(
                $page,
                $perPage,
                $data['pageTotal'] ?? 0,
                $games
            );
        } catch (\Exception $e) {
            throw new HowLongToBeatException("Error searching for games: " . $e->getMessage());
        }
    }

    /**
     * Search for a game by its ID
     *
     * @param string|int $gameId
     * @return Game
     */
    public function searchById($gameId)
    {
        if (is_string($gameId)) {
            $gameId = intval($gameId);
        }

        $gameData = $this->getGameDataById($gameId);

        if (!$gameData) {
            throw new HowLongToBeatException("Game not found with ID: {$gameId}");
        }

        return new Game([
            'id' => $gameData['game_id'] ?? 0,
            'name' => $gameData['game_name'] ?? '',
            'image_url' => $gameData['game_image'] ? "https://howlongtobeat.com/games/" . $gameData['game_image'] : null,
            'main_story_time' => $gameData['comp_main'] ?? null,
            'main_extra_time' => $gameData['comp_plus'] ?? null,
            'completionist_time' => $gameData['comp_100'] ?? null,
            'all_styles_time' => $gameData['comp_all'] ?? null,
        ]);
    }

    /**
     * Get the game data by its ID.
     *
     * @param int $gameId
     * @return array|null
     */
    private function getGameDataById(int $gameId)
    {
        $html = $this->get(self::BASE_URL . 'game/' . $gameId);

        if (preg_match('/<script id="__NEXT_DATA__" type="application\/json">(.+?)<\/script>/', $html, $matches)) {
            $json = json_decode($matches[1], true);

            return $json['props']['pageProps']['game']['data']['game'][0] ?? null;
        }

        throw new HowLongToBeatException("Unable to extract game JSON data for ID {$gameId}");
    }

    /**
     * Get the title of a game by its ID.
     *
     * @param int $gameId
     * @return string
     */
    private function getGameTitle(int $gameId)
    {
        $html = $this->get(self::BASE_URL . 'game/' . $gameId);

        preg_match('/<div class="GameHeader_profile_header__q_PID shadow_text">(.*?)<\/div>/', $html, $matches);

        // Remove HTML comments and trim whitespace
        $title = preg_replace('/<!--.*?-->/', '', $matches[1] ?? '');
        return trim($title);
    }
}
