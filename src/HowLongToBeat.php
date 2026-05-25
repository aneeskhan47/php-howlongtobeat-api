<?php

namespace AneesKhan47\HowLongToBeat;

use AneesKhan47\HowLongToBeat\Exceptions\HowLongToBeatException;
use AneesKhan47\HowLongToBeat\Models\Game;
use AneesKhan47\HowLongToBeat\Models\SearchResult;

class HowLongToBeat
{
    const BASE_URL = 'https://howlongtobeat.com/';
    const API_BLEED_URL = 'https://howlongtobeat.com/api/bleed';

    private static $cachedApiData = null;

    private $headers;
    private $apiData;

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
            'Referer: https://howlongtobeat.com/',
        ];

        $this->apiData = $this->getApiData();
    }

    /**
     * Fetch and cache API auth data from the bleed endpoint.
     *
     * @return array
     */
    private function getApiData(): array
    {
        if (self::$cachedApiData !== null) {
            return self::$cachedApiData;
        }

        $response = $this->get(self::API_BLEED_URL . '/init?t=' . time());
        $data = json_decode($response, true);

        if (isset($data['token'])) {
            self::$cachedApiData = $data;
            return $data;
        }

        throw new HowLongToBeatException('Could not retrieve API token');
    }

    /**
     * Make a POST request to the API.
     *
     * @param string $url
     * @param array $data
     * @param array $extraHeaders
     * @return array
     */
    private function post(string $url, array $data, array $extraHeaders = []): array
    {
        $response = $this->request('POST', $url, $data, $extraHeaders);

        return json_decode($response, true);
    }

    /**
     * Make an HTTP request.
     *
     * @param string $method
     * @param string $url
     * @param array $data
     * @param array $extraHeaders
     * @return string
     */
    private function request(string $method, string $url, array $data = [], array $extraHeaders = []): string
    {
        $headers = $this->headers;

        foreach ($extraHeaders as $key => $value) {
            $headers[] = "{$key}: {$value}";
        }

        $ch = curl_init($url);
        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
        ];

        if ($method === 'POST') {
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_POSTFIELDS] = json_encode($data);
        }

        curl_setopt_array($ch, $options);

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
     * Make a GET request to the API.
     *
     * @param string $url
     * @return string
     */
    private function get(string $url): string
    {
        return $this->request('GET', $url);
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
            $payload = [
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
            ];

            if (isset($this->apiData['hpKey'], $this->apiData['hpVal'])) {
                $payload[$this->apiData['hpKey']] = $this->apiData['hpVal'];
            }

            $headers = [
                'x-auth-token' => $this->apiData['token'],
            ];

            if (isset($this->apiData['hpKey'], $this->apiData['hpVal'])) {
                $headers['x-hp-key'] = $this->apiData['hpKey'];
                $headers['x-hp-val'] = $this->apiData['hpVal'];
            }

            $data = $this->post(self::API_BLEED_URL, $payload, $headers);

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
}
