<?php

namespace AneesKhan47\HowLongToBeat\Models;

class Game
{
    public $id;
    public $name;
    public $image_url;
    public $main_story_time;
    public $main_extra_time;
    public $completionist_time;
    public $all_styles_time;
    public $main_story_time_formatted;
    public $main_extra_time_formatted;
    public $completionist_time_formatted;
    public $all_styles_time_formatted;

    public function __construct(array $data)
    {
        $this->id = $data['id'] ?? 0;
        $this->name = $data['name'] ?? '';
        $this->image_url = $data['image_url'] ?? null;
        $this->main_story_time = $data['main_story_time'] ?? null;
        $this->main_extra_time = $data['main_extra_time'] ?? null;
        $this->completionist_time = $data['completionist_time'] ?? null;
        $this->all_styles_time = $data['all_styles_time'] ?? null;
        $this->main_story_time_formatted = $this->formatTimeSeconds($data['main_story_time'] ?? 0);
        $this->main_extra_time_formatted = $this->formatTimeSeconds($data['main_extra_time'] ?? 0);
        $this->completionist_time_formatted = $this->formatTimeSeconds($data['completionist_time'] ?? 0);
        $this->all_styles_time_formatted = $this->formatTimeSeconds($data['all_styles_time'] ?? 0);
    }

    private function formatTimeSeconds(int $time)
    {
        if ($time === 0) {
            return null;
        }

        return round($time / 60 / 60) . ' Hours';
    }
}
