import './bootstrap';
import * as bootstrap from 'bootstrap';
import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';
import Sort from '@alpinejs/sort';
import L from 'leaflet';
import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png';
import markerIcon from 'leaflet/dist/images/marker-icon.png';
import markerShadow from 'leaflet/dist/images/marker-shadow.png';
import ClassicEditor from '@ckeditor/ckeditor5-build-classic';

// CodeMirror 5 — syntax highlighting for header template editor
import CodeMirror from 'codemirror';
import 'codemirror/lib/codemirror.css';
import 'codemirror/theme/material-darker.css';
import 'codemirror/mode/xml/xml';
import 'codemirror/mode/htmlmixed/htmlmixed';
import 'codemirror/mode/css/css';
import 'codemirror/mode/javascript/javascript';

delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
    iconRetinaUrl: markerIcon2x,
    iconUrl: markerIcon,
    shadowUrl: markerShadow,
});

window.bootstrap = bootstrap;
window.L = L;
window.ClassicEditor = ClassicEditor;
window.CodeMirror = CodeMirror;

Alpine.plugin(Sort);

// Shared, app-wide replacement for browser confirm()/wire:confirm — see
// resources/views/partials/confirm-modal.blade.php for the modal markup that reads this store.
// Usage: @click="$store.confirm.open({ message: 'Delete this?' }).then(ok => ok && $wire.delete(id))"
document.addEventListener('alpine:init', () => {
    Alpine.store('confirm', {
        show: false,
        title: 'Are you sure?',
        message: '',
        confirmText: 'Confirm',
        variant: 'danger',
        _resolve: null,
        open({ title = 'Are you sure?', message = '', confirmText = 'Confirm', variant = 'danger' } = {}) {
            this.title = title;
            this.message = message;
            this.confirmText = confirmText;
            this.variant = variant;
            this.show = true;
            return new Promise((resolve) => {
                this._resolve = resolve;
            });
        },
        confirm() {
            this.show = false;
            if (this._resolve) this._resolve(true);
        },
        cancel() {
            this.show = false;
            if (this._resolve) this._resolve(false);
        },
    });

    // Shared, app-wide toast/notification queue — see resources/views/partials/toast-container.blade.php.
    // Fed by Livewire's $this->dispatch('notify', type: 'success', message: '...') (see App\Livewire\Concerns\Notifies),
    // caught here via Alpine's own `x-on:notify.window` in the partial (a real browser CustomEvent, no
    // separate Livewire.on() JS wiring needed — same convention this app already used for `settings-saved`).
    Alpine.store('toasts', {
        items: [],
        push(type, message) {
            const id = Date.now() + Math.random();
            this.items.push({ id, type, message });
            setTimeout(() => this.dismiss(id), 5000);
        },
        dismiss(id) {
            this.items = this.items.filter((t) => t.id !== id);
        },
    });
});

Livewire.start();
