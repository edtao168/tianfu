{{-- resources/views/livewire/finance/partials/_photo-memo.blade.php --}}

@props([
    'memoModel' => 'memo',
    'memoPlaceholder' => '請輸入備註',
    'class' => 'mb-5',
    'photoLabel' => '照片',
    'showPhoto' => true,
    'memoRows' => 3,
])

<div class="grid grid-cols-5 gap-3 {{ $class }}">
    {{-- 照片區塊（可選） --}}
    @if($showPhoto)
        <div class="col-span-2">
            {{-- 1. 使用 label 作為容器，點擊此區塊內任何地方都會自動觸發內部的 input file --}}
            <label class="aspect-square bg-stone-50 rounded-2xl border-2 border-dashed border-stone-300 
                        hover:border-stone-400 transition-all cursor-pointer 
                        flex flex-col items-center justify-center
                        dark:bg-stone-800 dark:border-stone-700 dark:hover:border-stone-600 relative overflow-hidden">
                
                {{-- 2. 隱藏的實體 Input 放在這裡 --}}
                <input type="file" wire:model="photo" accept="image/*" class="hidden" id="photo-input">

                {{-- 3. 預覽邏輯：如果後端有暫存新圖片，直接顯示暫存圖 --}}
                @if(isset($photo) && method_exists($photo, 'temporaryUrl'))
                    <img src="{{ $photo->temporaryUrl() }}" class="absolute inset-0 w-full h-full object-cover">
                {{-- 4. 預覽邏輯：如果後端有歷史圖片，顯示歷史圖（注意：後端引入 Component 時要有此屬性） --}}
                @elseif(isset($existingPhotoPath) && $existingPhotoPath)
                    <img src="{{ Storage::url($existingPhotoPath) }}" class="absolute inset-0 w-full h-full object-cover">
                @else
                    {{-- 5. 沒照片時，顯示預設的 SVG 與文字標籤 --}}
                    <svg class="w-8 h-8 text-stone-400 dark:text-stone-500 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <span class="text-xs text-stone-500 dark:text-stone-400 font-medium">{{ $photoLabel }}</span>
                @endif
                
                {{-- Livewire 上傳中的 Loading 提示狀態 --}}
                <div wire:loading wire:target="photo" class="absolute inset-0 bg-stone-900/50 flex items-center justify-center text-xs text-white">
                    上傳中...
                </div>
            </label>
        </div>
        <div class="col-span-3">
            <div class="relative w-full h-full min-h-[80px] bg-stone-100/50 dark:bg-slate-900/60 px-3 py-2 rounded-xl border border-stone-200 dark:border-stone-700">
                <textarea wire:model="{{ $memoModel }}"
                          placeholder="{{ $memoPlaceholder }}"
                          class="w-full h-full bg-transparent focus:outline-none
                                 text-stone-900 dark:text-stone-100 
                                 placeholder:text-stone-300 dark:placeholder:text-stone-600 
                                 font-medium resize-none text-sm"
                          style="min-height: 60px;"></textarea>
            </div>
        </div>
    @else
        <div class="col-span-5">
            <div class="relative w-full bg-stone-100/50 dark:bg-slate-900/60 px-3 py-2 rounded-xl border border-stone-200 dark:border-stone-700">
                <textarea wire:model="{{ $memoModel }}"
                          placeholder="{{ $memoPlaceholder }}"
                          rows="{{ $memoRows }}"
                          class="w-full bg-transparent focus:outline-none
                                 text-stone-900 dark:text-stone-100 
                                 placeholder:text-stone-300 dark:placeholder:text-stone-600 
                                 font-medium resize-none text-sm"></textarea>
            </div>
        </div>
    @endif
</div>