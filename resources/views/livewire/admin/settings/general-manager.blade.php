<div>
    @include('admin.partials.breadcrumb', ['items' => [['label' => 'Settings']]])

    <div class="ssm-page-header">
        <div class="ssm-page-header__title">Settings</div>
        <div class="ssm-page-header__subtitle">Site identity, contact details, homepage behavior, and outgoing email.</div>
    </div>

    {{--
        Alpine-driven tabs, not Bootstrap's native data-bs-toggle="tab" — Bootstrap's tab JS tracks the
        active tab purely via DOM classes it sets itself, which a Livewire morph after ANY wire:submit
        on either tab (Save Settings, Save SMTP Settings, Send Test Email) would silently reset back to
        whatever the server-rendered markup hardcodes as "active", snapping the user back to the General
        tab right after they submit the SMTP form. Alpine's local x-data state survives a Livewire morph
        (the whole point of Livewire+Alpine interop), so this is the same "prefer Alpine-only local UI
        state over a Livewire round-trip" principle this app's own README already flags after the Module 6
        chat-widget toggle bug — see docs/modules/README.md.
    --}}
    <div x-data="{ tab: 'general' }">
        <ul class="nav nav-tabs mb-4" role="tablist">
            <li class="nav-item" role="presentation">
                <button type="button" class="nav-link" :class="{ active: tab === 'general' }" @click="tab = 'general'">
                    <i class="bi bi-gear me-1"></i> General
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button type="button" class="nav-link" :class="{ active: tab === 'smtp' }" @click="tab = 'smtp'">
                    <i class="bi bi-envelope-gear me-1"></i> SMTP Settings
                </button>
            </li>
        </ul>

        <div x-show="tab === 'general'">
            <form wire:submit="save">
                <div class="card mb-4">
                    <div class="card-header fw-semibold"><i class="bi bi-building me-2"></i>Site Information</div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Site Name</label>
                            <input type="text" wire:model="siteName" class="form-control @error('siteName') is-invalid @enderror">
                            @error('siteName') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Logo</label>
                                @if ($logoUrl)
                                    <div class="mb-2"><img src="{{ $logoUrl }}" alt="Logo" style="max-height: 60px;"></div>
                                @endif
                                <input type="file" wire:model="logo" class="form-control @error('logo') is-invalid @enderror">
                                @error('logo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                <div wire:loading wire:target="logo" class="small text-muted mt-1">Uploading...</div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Favicon</label>
                                @if ($faviconUrl)
                                    <div class="mb-2"><img src="{{ $faviconUrl }}" alt="Favicon" style="max-height: 32px;"></div>
                                @endif
                                <input type="file" wire:model="favicon" class="form-control @error('favicon') is-invalid @enderror">
                                @error('favicon') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                <div wire:loading wire:target="favicon" class="small text-muted mt-1">Uploading...</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header fw-semibold"><i class="bi bi-telephone me-2"></i>Contact Details</div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Contact Email</label>
                            <input type="text" wire:model="contactEmail" class="form-control @error('contactEmail') is-invalid @enderror">
                            @error('contactEmail') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Contact Phone</label>
                            <input type="text" wire:model="contactPhone" class="form-control @error('contactPhone') is-invalid @enderror">
                            @error('contactPhone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea wire:model="contactAddress" rows="2" class="form-control @error('contactAddress') is-invalid @enderror"></textarea>
                            @error('contactAddress') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header fw-semibold"><i class="bi bi-envelope-check me-2"></i>Contact Form Notifications</div>
                    <div class="card-body">
                        <div class="form-check form-switch mb-3">
                            <input type="checkbox" wire:model="contactNotifyEnabled" class="form-check-input" id="contact-notify-enabled" role="switch">
                            <label class="form-check-label" for="contact-notify-enabled">Email me when someone submits the contact form</label>
                        </div>
                        <div class="mb-1">
                            <label class="form-label">Send notifications to</label>
                            <input type="text" wire:model="contactNotifyEmail" placeholder="e.g. support@yourcompany.com or hr@yourcompany.com" class="form-control @error('contactNotifyEmail') is-invalid @enderror">
                            @error('contactNotifyEmail') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <div class="form-text">Leave blank to use the Contact Email above instead. Every submission is always saved under CRM &rarr; Contact Messages regardless of this setting — this only controls the email alert.</div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header fw-semibold"><i class="bi bi-globe me-2"></i>Regional</div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Currency</label>
                                <select wire:model="currency" class="form-select @error('currency') is-invalid @enderror">
                                    @foreach ($currencies as $code)
                                        <option value="{{ $code }}">{{ $code }}</option>
                                    @endforeach
                                </select>
                                @error('currency') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Timezone</label>
                                <select wire:model="timezone" class="form-select @error('timezone') is-invalid @enderror">
                                    @foreach ($timezones as $tz)
                                        <option value="{{ $tz }}">{{ $tz }}</option>
                                    @endforeach
                                </select>
                                @error('timezone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header fw-semibold"><i class="bi bi-layout-text-window me-2"></i>Homepage Sections</div>
                    <div class="card-body">
                        <div class="form-check form-switch">
                            <input type="checkbox" wire:model="showFeaturedProperties" class="form-check-input" id="featured-properties-toggle">
                            <label class="form-check-label" for="featured-properties-toggle">
                                Show "Property For Sale" section
                            </label>
                        </div>
                        <p class="text-muted small mt-2 mb-0">
                            Shows the latest 6 approved properties from <a href="{{ route('admin.properties.index') }}">Admin &raquo; Properties</a>. Turn off to hide the whole section from the homepage.
                        </p>

                        <div class="form-check form-switch mt-3">
                            <input type="checkbox" wire:model="showReviewsSection" class="form-check-input" id="reviews-section-toggle">
                            <label class="form-check-label" for="reviews-section-toggle">
                                Show "Good Reviews by Customers" section
                            </label>
                        </div>
                        <p class="text-muted small mt-2 mb-0">
                            Manage the reviews themselves under <a href="{{ route('admin.reviews.index') }}">Admin &raquo; Reviews</a>. Turn off to hide the whole section from the homepage.
                        </p>

                        <div class="form-check form-switch mt-3">
                            <input type="checkbox" wire:model="showProjectsSection" class="form-check-input" id="projects-section-toggle">
                            <label class="form-check-label" for="projects-section-toggle">
                                Show "Featured Projects" section
                            </label>
                        </div>
                        <p class="text-muted small mt-2 mb-0">
                            Pulls projects marked as Featured from <a href="{{ route('admin.projects.index') }}">Admin &raquo; Projects</a>. Turn off to hide the whole section from the homepage.
                        </p>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header fw-semibold"><i class="bi bi-bell me-2"></i>Notifications</div>
                    <div class="card-body">
                        <div class="form-check form-switch">
                            <input type="checkbox" wire:model="smsNotificationsEnabled" class="form-check-input" id="sms-toggle">
                            <label class="form-check-label" for="sms-toggle">
                                Enable SMS Notifications
                                <span class="badge text-bg-secondary ms-1">Coming soon</span>
                            </label>
                        </div>
                        <p class="text-muted small mt-2 mb-0">
                            No SMS provider is configured yet. This toggle is a placeholder for a future integration.
                        </p>
                    </div>
                </div>

                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="save">
                        <span wire:loading wire:target="save" class="spinner-border spinner-border-sm me-2"></span>
                        Save Settings
                    </button>
                </div>
            </form>

            <div class="card mt-4 border-warning">
                <div class="card-header fw-semibold text-warning-emphasis"><i class="bi bi-exclamation-triangle me-2"></i>Maintenance Mode</div>
                <div class="card-body">
                    <p class="mb-3">
                        Current status:
                        @if ($maintenanceMode)
                            <span class="badge text-bg-danger">Down for Maintenance</span>
                        @else
                            <span class="badge text-bg-success">Live</span>
                        @endif
                    </p>
                    <button type="button" class="btn {{ $maintenanceMode ? 'btn-success' : 'btn-outline-danger' }}"
                        @click="$store.confirm.open({ message: 'Are you sure you want to {{ $maintenanceMode ? 'bring the site back up' : 'take the site down for maintenance' }}?', variant: 'danger' }).then(ok => ok && $wire.toggleMaintenanceMode())">
                        <i class="bi {{ $maintenanceMode ? 'bi-power' : 'bi-exclamation-octagon' }}"></i>
                        {{ $maintenanceMode ? 'Bring Site Back Up' : 'Take Site Down' }}
                    </button>
                </div>
            </div>
        </div>

        <div x-show="tab === 'smtp'" x-cloak>
            <div class="alert alert-info">
                <i class="bi bi-info-circle-fill me-1"></i>
                Leave <strong>Enable custom SMTP</strong> off to keep using the mail configuration already set up in
                the server's <code>.env</code> file. Turn it on only once you've filled in real credentials below —
                an incomplete configuration would otherwise break every email this site sends.
            </div>

            <form wire:submit="saveSmtp">
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="form-check form-switch mb-4">
                            <input type="checkbox" wire:model="smtpEnabled" class="form-check-input" id="smtp-enabled" role="switch">
                            <label class="form-check-label fw-semibold" for="smtp-enabled">Enable custom SMTP</label>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label">SMTP Host</label>
                                <input type="text" wire:model="smtpHost" placeholder="e.g. smtp.gmail.com" class="form-control @error('smtpHost') is-invalid @enderror">
                                @error('smtpHost') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Port</label>
                                <input type="text" wire:model="smtpPort" placeholder="587" class="form-control @error('smtpPort') is-invalid @enderror">
                                @error('smtpPort') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Username</label>
                                <input type="text" wire:model="smtpUsername" autocomplete="off" class="form-control @error('smtpUsername') is-invalid @enderror">
                                @error('smtpUsername') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">
                                    Password
                                    @if ($hasStoredPassword)
                                        <span class="badge text-bg-success ms-1">Set</span>
                                    @endif
                                </label>
                                <input type="password" wire:model="smtpPassword" autocomplete="new-password"
                                    placeholder="{{ $hasStoredPassword ? 'Leave blank to keep the current password' : '' }}"
                                    class="form-control @error('smtpPassword') is-invalid @enderror">
                                @error('smtpPassword') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Encryption</label>
                                <select wire:model="smtpEncryption" class="form-select">
                                    <option value="tls">TLS</option>
                                    <option value="ssl">SSL</option>
                                    <option value="">None</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">From Address</label>
                                <input type="email" wire:model="smtpFromAddress" class="form-control @error('smtpFromAddress') is-invalid @enderror">
                                @error('smtpFromAddress') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">From Name</label>
                                <input type="text" wire:model="smtpFromName" placeholder="{{ config('app.name') }}" class="form-control @error('smtpFromName') is-invalid @enderror">
                                @error('smtpFromName') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save"></i> Save SMTP Settings
                </button>
            </form>

            <div class="card mt-4">
                <div class="card-body">
                    <h2 class="h6 ssm-form-section-title"><i class="bi bi-envelope-paper"></i> Send a Test Email</h2>
                    <p class="text-muted small">Save your settings above first, then send a test to confirm they actually work.</p>
                    <div class="row g-2 align-items-start">
                        <div class="col-md-6">
                            <input type="email" wire:model="testEmailAddress" placeholder="you@example.com" class="form-control @error('testEmailAddress') is-invalid @enderror">
                            @error('testEmailAddress') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <button type="button" wire:click="sendTestEmail" wire:loading.attr="disabled" wire:target="sendTestEmail" class="btn btn-outline-primary">
                                <span wire:loading.remove wire:target="sendTestEmail"><i class="bi bi-send"></i> Send Test Email</span>
                                <span wire:loading wire:target="sendTestEmail">Sending&hellip;</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
