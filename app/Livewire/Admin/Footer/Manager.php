<?php

namespace App\Livewire\Admin\Footer;

use App\Enums\FooterWidgetType;
use App\Livewire\Concerns\Notifies;
use App\Models\FooterSetting;
use App\Models\FooterWidget;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class Manager extends Component
{
    use Notifies;

    // Footer-level settings
    public FooterSetting $footerSetting;

    public string $footerName = '';

    public string $footerStatus = 'active';

    public int $footerColumns = 3;

    // Bottom bar fields
    public string $footerCompanyName = '';

    public string $footerCopyrightText = '';

    public string $footerCopyrightTagline = '';

    public array $footerSocialLinks = [];

    // Bottom bar toggles
    public bool $showCompanyName = true;

    public bool $showTagline = true;

    public bool $showSocialLinks = true;

    public bool $showCopyright = true;

    // Bottom bar positions (left/right)
    public string $positionCompanyName = 'left';

    public string $positionTagline = 'left';

    public string $positionSocialLinks = 'right';

    public string $positionCopyright = 'right';

    // Widget form
    public bool $showForm = false;

    public ?int $editingId = null;

    public string $title = '';

    public string $type = 'text';

    public int $column = 0;

    public string $body = '';

    public array $links = [];

    public bool $isActive = true;

    public function mount(): void
    {
        $this->footerSetting = FooterSetting::current()
            ?? FooterSetting::create([
                'name' => 'Main Footer',
                'status' => 'active',
                'columns' => 3,
            ]);
        $this->footerName = $this->footerSetting->name;
        $this->footerStatus = $this->footerSetting->status;
        $this->footerColumns = $this->footerSetting->columns;
        $this->footerCompanyName = $this->footerSetting->company_name ?? '';
        $this->footerCopyrightText = $this->footerSetting->copyright_text ?? '';
        $this->footerCopyrightTagline = $this->footerSetting->copyright_tagline ?? '';
        $this->footerSocialLinks = $this->footerSetting->social_links ?? [];
        $this->showCompanyName = $this->footerSetting->show_company_name ?? true;
        $this->showTagline = $this->footerSetting->show_tagline ?? true;
        $this->showSocialLinks = $this->footerSetting->show_social_links ?? true;
        $this->showCopyright = $this->footerSetting->show_copyright ?? true;
        $this->positionCompanyName = $this->footerSetting->position_company_name ?? 'left';
        $this->positionTagline = $this->footerSetting->position_tagline ?? 'left';
        $this->positionSocialLinks = $this->footerSetting->position_social_links ?? 'right';
        $this->positionCopyright = $this->footerSetting->position_copyright ?? 'right';

        if (empty($this->footerSocialLinks)) {
            $this->footerSocialLinks = [['platform' => '', 'url' => '', 'icon' => '']];
        }
    }

    public function saveFooterSettings(): void
    {
        $this->validate([
            'footerName' => 'required|string|max:255',
            'footerStatus' => 'required|in:active,inactive',
            'footerColumns' => 'required|integer|min:2|max:4',
            'footerCompanyName' => 'nullable|string|max:255',
            'footerCopyrightText' => 'nullable|string|max:255',
            'footerCopyrightTagline' => 'nullable|string|max:255',
            'footerSocialLinks.*.platform' => 'nullable|string|max:50',
            'footerSocialLinks.*.url' => 'nullable|string|max:500',
            'footerSocialLinks.*.icon' => 'nullable|string|max:100',
        ]);

        $socialLinks = array_values(array_filter($this->footerSocialLinks, fn ($l) =>
            trim($l['url'] ?? '') !== ''
        ));

        $this->footerSetting->update([
            'name' => $this->footerName,
            'status' => $this->footerStatus,
            'columns' => $this->footerColumns,
            'company_name' => $this->footerCompanyName,
            'copyright_text' => $this->footerCopyrightText,
            'copyright_tagline' => $this->footerCopyrightTagline,
            'social_links' => $socialLinks,
            'show_company_name' => $this->showCompanyName,
            'show_tagline' => $this->showTagline,
            'show_social_links' => $this->showSocialLinks,
            'show_copyright' => $this->showCopyright,
            'position_company_name' => $this->positionCompanyName,
            'position_tagline' => $this->positionTagline,
            'position_social_links' => $this->positionSocialLinks,
            'position_copyright' => $this->positionCopyright,
        ]);

        Cache::forget('footer_widgets');

        $this->notifySuccess('Footer settings saved.');
    }

    public function createWidget(int $column = 0): void
    {
        $this->resetForm();
        $this->column = $column;
        $this->showForm = true;
    }

    public function editWidget(int $id): void
    {
        $widget = FooterWidget::findOrFail($id);

        $this->editingId = $widget->id;
        $this->title = (string) $widget->title;
        $this->type = $widget->type;
        $this->column = $widget->column;
        $this->isActive = $widget->is_active;

        $content = $widget->content ?? [];
        $this->body = $content['body'] ?? '';
        $this->links = $content['links'] ?? [];

        if (empty($this->links)) {
            $this->links = [['label' => '', 'url' => '']];
        }

        $this->showForm = true;
    }

    public function addLinkRow(): void
    {
        $this->links[] = ['label' => '', 'url' => ''];
    }

    public function removeLinkRow(int $index): void
    {
        unset($this->links[$index]);
        $this->links = array_values($this->links);
    }

    public function saveWidget(): void
    {
        $this->validate([
            'title' => 'nullable|string|max:255',
            'type' => 'required|in:'.implode(',', array_map(fn ($c) => $c->value, FooterWidgetType::cases())),
            'column' => 'required|integer|min:0|max:3',
            'body' => 'nullable|string',
            'links.*.label' => 'nullable|string|max:255',
            'links.*.url' => 'nullable|string|max:255',
        ]);

        $content = $this->type === 'text'
            ? ['body' => $this->body]
            : ['links' => array_values(array_filter($this->links, fn ($l) => trim($l['label'] ?? '') !== ''))];

        $widget = $this->editingId
            ? FooterWidget::findOrFail($this->editingId)
            : new FooterWidget(['order' => (FooterWidget::where('column', $this->column)->max('order') ?? -1) + 1]);

        $widget->fill([
            'title' => $this->title ?: null,
            'type' => $this->type,
            'content' => $content,
            'column' => $this->column,
            'is_active' => $this->isActive,
        ])->save();

        $this->closeForm();
        Cache::forget('footer_widgets');
    }

    public function deleteWidget(int $id): void
    {
        $widget = FooterWidget::findOrFail($id);
        $title = $widget->title ?: ucfirst($widget->type);
        $widget->delete();
        Cache::forget('footer_widgets');
        $this->notifySuccess("\"{$title}\" widget deleted.");
    }

    public function toggleActive(int $id): void
    {
        $widget = FooterWidget::findOrFail($id);
        $widget->update(['is_active' => ! $widget->is_active]);
        Cache::forget('footer_widgets');
    }

    public function reorder(int $itemId, int $position): void
    {
        $item = FooterWidget::findOrFail($itemId);

        $siblings = FooterWidget::where('column', $item->column)
            ->orderBy('order')
            ->get()
            ->reject(fn (FooterWidget $w) => $w->id === $item->id)
            ->values();

        $siblings->splice($position, 0, [$item]);

        foreach ($siblings as $index => $sibling) {
            if ($sibling->order !== $index) {
                $sibling->update(['order' => $index]);
            }
        }

        Cache::forget('footer_widgets');
    }

    // ---- Social Link inline helpers ----

    public function addSocialLinkRow(): void
    {
        $this->footerSocialLinks[] = ['platform' => '', 'url' => '', 'icon' => ''];
    }

    public function removeSocialLinkRow(int $index): void
    {
        unset($this->footerSocialLinks[$index]);
        $this->footerSocialLinks = array_values($this->footerSocialLinks);

        if (empty($this->footerSocialLinks)) {
            $this->footerSocialLinks = [['platform' => '', 'url' => '', 'icon' => '']];
        }
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'title', 'body', 'links']);
        $this->type = 'text';
        $this->column = 0;
        $this->isActive = true;
        $this->links = [['label' => '', 'url' => '']];
        $this->resetErrorBag();
    }

    public function render()
    {
        $widgetsByColumn = FooterWidget::orderBy('order')->get()->groupBy('column');

        return view('livewire.admin.footer.manager', [
            'widgetsByColumn' => $widgetsByColumn,
            'types' => FooterWidgetType::cases(),
        ])->extends('admin.layouts.app')->section('content');
    }
}
