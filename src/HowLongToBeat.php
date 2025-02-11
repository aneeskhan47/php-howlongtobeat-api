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
        $this->apiEndpoint = $this->findApiEndpoint();
    }

    /**
     * Find the API endpoint from the main page script
     *
     * @return string
     */
    private function findApiEndpoint(): string
    {
        $html = $this->get(self::BASE_URL);

        // Find all script tags with _app- in their src
        if (!preg_match_all('/<script[^>]*src=["\']([^"\']*_app-[^"\']*\.js)["\'][^>]*>/i', $html, $matches)) {
            throw new HowLongToBeatException("Could not find app script");
        }

        // Get the first matching script
        $scriptUrl = self::BASE_URL . ltrim($matches[1][0], '/');
        $scriptContent = $this->get($scriptUrl);

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
            $data = $this->post(self::BASE_URL . 'api/s/' . $this->apiEndpoint, [
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
    public function searchById($gameId): Game
    {
        if (is_string($gameId)) {
            $gameId = intval($gameId);
        }

        $gameTitle = $this->getGameTitle($gameId);

        try {
            $data = $this->post(self::BASE_URL . 'api/s/' . $this->apiEndpoint, [
                'searchType' => 'games',
                'searchTerms' => [$gameTitle],
                'searchPage' => 1,
                'size' => 1,
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

            if (empty($data['data'][0])) {
                throw new HowLongToBeatException("Game not found with ID: {$gameId}");
            }

            $gameData = $data['data'][0];

            return new Game([
                'id' => $gameData['game_id'] ?? 0,
                'name' => $gameData['game_name'] ?? '',
                'image_url' => $gameData['game_image'] ? "https://howlongtobeat.com/games/" . $gameData['game_image'] : null,
                'main_story_time' => $gameData['comp_main'] ?? null,
                'main_extra_time' => $gameData['comp_plus'] ?? null,
                'completionist_time' => $gameData['comp_100'] ?? null,
                'all_styles_time' => $gameData['comp_all'] ?? null,
            ]);
        } catch (\Exception $e) {
            throw new HowLongToBeatException("Error getting game details: " . $e->getMessage());
        }
    }

    /**
     * Get the title of a game by its ID.
     *
     * @param int $gameId
     * @return string
     */
    private function getGameTitle(int $gameId): string
    {
        $html = $this->get(self::BASE_URL . 'game/' . $gameId);

        preg_match('/<div class="GameHeader_profile_header__q_PID shadow_text">(.*?)<\/div>/', $html, $matches);

        // Remove HTML comments and trim whitespace
        $title = preg_replace('/<!--.*?-->/', '', $matches[1] ?? '');
        return trim($title);
    }
}
