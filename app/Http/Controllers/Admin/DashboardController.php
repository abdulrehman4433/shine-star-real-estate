<?php

namespace App\Http\Controllers\Admin;

use App\Enums\LeadStatus;
use App\Enums\PropertyStatus;
use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\Property;
use App\Models\PropertyInquiry;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $propertyStatusCounts = collect(PropertyStatus::cases())
            ->mapWithKeys(fn (PropertyStatus $status) => [
                $status->value => Property::where('status', $status->value)->count(),
            ]);

        $leadStatusCounts = collect(LeadStatus::cases())
            ->mapWithKeys(fn (LeadStatus $status) => [
                $status->value => Lead::where('status', $status->value)->count(),
            ]);

        return view('admin.dashboard', [
            'propertiesCount' => Property::count(),
            'leadsCount' => Lead::count(),
            'agentsCount' => User::whereHas('roles', fn ($q) => $q->whereIn('name', [
                RoleName::Agent->value, RoleName::Agency->value,
            ]))->count(),
            'inquiriesCount' => PropertyInquiry::count(),
            'propertyStatusCounts' => $propertyStatusCounts,
            'leadStatusCounts' => $leadStatusCounts,
        ]);
    }
}
