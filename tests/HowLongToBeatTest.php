<?php

use AneesKhan47\HowLongToBeat\HowLongToBeat;
use AneesKhan47\HowLongToBeat\Models\Game;
use AneesKhan47\HowLongToBeat\Models\SearchResult;
use AneesKhan47\HowLongToBeat\Exceptions\HowLongToBeatException;
use PHPUnit\Framework\TestCase;

class HowLongToBeatTest extends TestCase
{
    protected $hltb;

    protected function setUp(): void
    {
        $this->hltb = new HowLongToBeat();
    }

    public function testSearchByTitleReturnsSearchResult()
    {
        $result = $this->hltb->searchByTitle('The Last of Us');

        $this->assertInstanceOf(SearchResult::class, $result);
        $this->assertGreaterThan(0, $result->count());
        $this->assertInstanceOf(Game::class, $result->items()[0]);
    }

    public function testSearchByTitleWithPagination()
    {
        $result = $this->hltb->searchByTitle('Mario', 1, 10);

        $this->assertEquals(10, $result->perPage());
        $this->assertEquals(1, $result->currentPage());
        $this->assertLessThanOrEqual(10, $result->count());
    }

    public function testSearchByTitleWithInvalidPerPage()
    {
        $this->expectException(HowLongToBeatException::class);
        $this->expectExceptionMessage('Maximum results per page is 25');
        $this->hltb->searchByTitle('Mario', 1, 30);
    }

    public function testSearchByIdReturnsGame()
    {
        $game = $this->hltb->searchById(10270); // The Witcher 3: Wild Hunt

        $this->assertInstanceOf(Game::class, $game);
        $this->assertEquals('The Witcher 3: Wild Hunt', $game->name);
        $this->assertEquals(10270, $game->id);
        $this->assertNotEmpty($game->name);
    }

    public function testSearchByInvalidId()
    {
        $this->expectException(HowLongToBeatException::class);
        $this->hltb->searchById(999999999);
    }

    public function testGameProperties()
    {
        $result = $this->hltb->searchByTitle('The Last of Us');
        $game = $result->items()[0];

        $this->assertObjectHasProperty('id', $game);
        $this->assertObjectHasProperty('name', $game);
        $this->assertObjectHasProperty('image_url', $game);
        $this->assertObjectHasProperty('main_story_time', $game);
        $this->assertObjectHasProperty('main_extra_time', $game);
        $this->assertObjectHasProperty('completionist_time', $game);
        $this->assertObjectHasProperty('all_styles_time', $game);
    }

    public function testSearchResultPagination()
    {
        $result = $this->hltb->searchByTitle('Mario', 1, 10);

        $this->assertTrue(is_int($result->currentPage()));
        $this->assertTrue(is_int($result->perPage()));
        $this->assertTrue(is_int($result->total()));
        $this->assertTrue(is_int($result->lastPage()));
        $this->assertTrue(is_bool($result->hasMorePages()));
        $this->assertTrue(is_bool($result->hasPages()));
        $this->assertTrue(is_bool($result->isEmpty()));
        $this->assertTrue(is_bool($result->isNotEmpty()));
    }

    public function testEmptySearchResult()
    {
        $result = $this->hltb->searchByTitle('xxxxxxxxxxxxxxxxxxx');

        $this->assertTrue($result->isEmpty());
        $this->assertFalse($result->isNotEmpty());
        $this->assertEquals(0, $result->count());
    }
}
