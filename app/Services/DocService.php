<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class DocService
{
    public function getArticles(): array
    {
        $path = resource_path('markdown/docs');
        if (!File::exists($path)) {
            return [];
        }

        return collect(File::files($path))
            ->map(function ($file) {
                $slug = $file->getFilenameWithoutExtension();
                $content = File::get($file->getPathname());
                // 抓取第一行 # 作為標題
                $title = Str::of($content)->after('# ')->before("\n")->trim();

                return [
                    'slug' => $slug,
                    'title' => $title->isNotEmpty() ? (string) $title : $slug,
                    'content' => $content,
                ];
            })
            ->toArray();
    }

    public function getArticleHtml(string $slug): string
    {
        $filePath = resource_path("markdown/docs/{$slug}.md");
        if (!File::exists($filePath)) {
            return '<p>找不到該章節內容。</p>';
        }

        $markdown = File::get($filePath);
        // 使用 Str::markdown() (Laravel 內建) 轉換成 HTML
        return Str::markdown($markdown);
    }
}