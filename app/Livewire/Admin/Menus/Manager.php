<?php

namespace App\Livewire\Admin\Menus;

use App\Enums\MenuLocation;
use App\Livewire\Concerns\Notifies;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class Manager extends Component
{
    use Notifies;

    public ?int $activeMenuId = null;

    public bool $showMenuForm = false;

    public string $menuName = '';

    public string $menuLocation = 'header';

    public bool $showItemForm = false;

    public ?int $editingItemId = null;

    public string $label = '';

    public string $url = '';

    public string $pageId = '';

    public string $parentId = '';

    public string $target = '_self';

    public bool $isActive = true;

    public function mount(): void
    {
        $this->activeMenuId = Menu::query()->orderBy('id')->value('id');
    }

    public function selectMenu(int $menuId): void
    {
        $this->activeMenuId = $menuId;
    }

    public function createMenu(): void
    {
        $this->resetMenuForm();
        $this->showMenuForm = true;
    }

    public function saveMenu(): void
    {
        $this->validate([
            'menuName' => 'required|string|max:255',
            'menuLocation' => 'required|in:'.implode(',', array_map(fn ($c) => $c->value, MenuLocation::cases())),
        ]);

        $menu = Menu::create(['name' => $this->menuName, 'location' => $this->menuLocation]);

        $this->activeMenuId = $menu->id;
        $this->showMenuForm = false;
        $this->forgetMenuCache();
    }

    public function deleteMenu(int $menuId): void
    {
        $menu = Menu::findOrFail($menuId);
        $name = $menu->name;
        $menu->delete();

        if ($this->activeMenuId === $menuId) {
            $this->activeMenuId = Menu::query()->orderBy('id')->value('id');
        }

        $this->forgetMenuCache();
        $this->notifySuccess("\"{$name}\" menu deleted.");
    }

    public function createItem(): void
    {
        $this->resetItemForm();
        $this->showItemForm = true;
    }

    public function editItem(int $itemId): void
    {
        $item = MenuItem::findOrFail($itemId);

        $this->editingItemId = $item->id;
        $this->label = $item->label;
        $this->url = (string) $item->url;
        $this->pageId = (string) $item->page_id;
        $this->parentId = (string) $item->parent_id;
        $this->target = $item->target;
        $this->isActive = $item->is_active;
        $this->showItemForm = true;
    }

    public function saveItem(): void
    {
        $this->validate([
            'label' => 'required|string|max:255',
            'url' => 'nullable|string|max:255',
            'parentId' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    if ($this->editingItemId && $value == $this->editingItemId) {
                        $fail('A menu item cannot be its own parent.');
                    }
                },
            ],
        ]);

        $item = $this->editingItemId
            ? MenuItem::findOrFail($this->editingItemId)
            : new MenuItem([
                'menu_id' => $this->activeMenuId,
                'order' => (MenuItem::where('menu_id', $this->activeMenuId)->max('order') ?? -1) + 1,
            ]);

        $item->fill([
            'label' => $this->label,
            'url' => $this->url ?: null,
            'page_id' => $this->pageId ?: null,
            'parent_id' => $this->parentId ?: null,
            'target' => $this->target,
            'is_active' => $this->isActive,
        ])->save();

        $this->closeItemForm();
        $this->forgetMenuCache();
    }

    public function deleteItem(int $itemId): void
    {
        $item = MenuItem::findOrFail($itemId);
        $label = $item->label;
        $item->delete();
        $this->forgetMenuCache();
        $this->notifySuccess("\"{$label}\" deleted.");
    }

    public function toggleItemActive(int $itemId): void
    {
        $item = MenuItem::findOrFail($itemId);
        $item->update(['is_active' => ! $item->is_active]);
        $this->forgetMenuCache();
    }

    public function reorder(int $itemId, int $position): void
    {
        $item = MenuItem::findOrFail($itemId);

        $siblings = MenuItem::where('menu_id', $item->menu_id)
            ->where('parent_id', $item->parent_id)
            ->orderBy('order')
            ->get()
            ->reject(fn (MenuItem $i) => $i->id === $item->id)
            ->values();

        $siblings->splice($position, 0, [$item]);

        foreach ($siblings as $index => $sibling) {
            if ($sibling->order !== $index) {
                $sibling->update(['order' => $index]);
            }
        }

        $this->forgetMenuCache();
    }

    public function closeItemForm(): void
    {
        $this->showItemForm = false;
        $this->resetItemForm();
    }

    public function closeMenuForm(): void
    {
        $this->showMenuForm = false;
        $this->resetMenuForm();
    }

    private function resetMenuForm(): void
    {
        $this->reset(['menuName', 'menuLocation']);
        $this->menuLocation = 'header';
        $this->resetErrorBag();
    }

    private function resetItemForm(): void
    {
        $this->reset(['editingItemId', 'label', 'url', 'pageId', 'parentId', 'target', 'isActive']);
        $this->target = '_self';
        $this->isActive = true;
        $this->resetErrorBag();
    }

    private function forgetMenuCache(): void
    {
        Cache::forget('menu.header');
        Cache::forget('menu.footer');
    }

    private function buildTree($items, ?int $parentId = null): array
    {
        return $items
            ->where('parent_id', $parentId)
            ->map(fn (MenuItem $item) => [
                'item' => $item,
                'children' => $this->buildTree($items, $item->id),
            ])
            ->values()
            ->all();
    }

    public function render()
    {
        $menus = Menu::orderBy('id')->get();
        $activeMenu = $this->activeMenuId ? Menu::find($this->activeMenuId) : null;

        $tree = [];
        $itemOptions = collect();

        if ($activeMenu) {
            $items = MenuItem::where('menu_id', $activeMenu->id)->orderBy('order')->get();
            $tree = $this->buildTree($items);
            $itemOptions = $items->reject(fn (MenuItem $i) => $i->id === $this->editingItemId);
        }

        return view('livewire.admin.menus.manager', [
            'menus' => $menus,
            'activeMenu' => $activeMenu,
            'tree' => $tree,
            'itemOptions' => $itemOptions,
            'pages' => Page::orderBy('title')->get(),
            'locations' => MenuLocation::cases(),
        ])->extends('admin.layouts.app')->section('content');
    }
}
