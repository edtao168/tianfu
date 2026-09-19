<div class="space-y-4">
    <!-- 頁面標題 -->
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold flex items-center gap-2">
            <x-icon name="o-book-open" class="w-7 h-7 text-teal-500" />
            操作手冊
        </h1>
    </div>

    <div class="grid grid-cols-12 gap-6">
        <!-- 目錄側邊欄 -->
        <div class="col-span-12 md:col-span-4 lg:col-span-3">
            <x-card shadow class="bg-base-100">
                <x-menu>
                    @foreach($articles as $doc)
                        <x-menu-item 
                            title="{{ $doc['title'] }}" 
                            wire:click="selectArticle('{{ $doc['slug'] }}')" 
                            class="{{ $currentSlug === $doc['slug'] ? 'bg-teal-500/10 text-teal-600 font-bold' : '' }}"
                        />
                    @endforeach
                </x-menu>
            </x-card>
        </div>

        <!-- 文章內容顯示區 -->
        <div class="col-span-12 md:col-span-8 lg:col-span-9">
            <x-card shadow class="bg-base-100">
                <article class="prose max-w-none dark:prose-invert prose-teal">
                    {!! $renderedHtml !!}
                </article>
            </x-card>
        </div>
    </div>
</div>