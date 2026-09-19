<?php // app/Livewire/Finance/DocsViewer.php

namespace App\Livewire\Finance;

use App\Services\DocService;
use Livewire\Component;

class DocsViewer extends Component
{
    public ?string $currentSlug = null;

    // 將 DocService 從 mount 參數中移除，改用 app(DocService::class) 取得
    public function mount(?string $slug = null)
    {
        $docService = app(DocService::class);
        $articles = $docService->getArticles();
        
        // 若未帶 slug 則預設載入第一個檔案
        $this->currentSlug = $slug ?? ($articles[0]['slug'] ?? '');
    }

    public function selectArticle(string $slug)
    {
        $this->currentSlug = $slug;
        $this->js("history.pushState(null, '', '/finance/docs/{$slug}')");
    }

    public function render(DocService $docService)
    {
        // Livewire 的 render() 方法支援自動注入 Service
        $articles = $docService->getArticles();
        $renderedHtml = $this->currentSlug ? $docService->getArticleHtml($this->currentSlug) : '';

        return view('livewire.finance.docs-viewer', [
            'articles' => $articles,
            'renderedHtml' => $renderedHtml,
        ])->layout('components.layouts.app');
    }
}