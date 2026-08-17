<?php

namespace App\Observers;

use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\PropertyInquiry;

class PropertyInquiryObserver
{
    /**
     * Every inquiry automatically becomes a lead in the CRM pipeline.
     */
    public function created(PropertyInquiry $propertyInquiry): void
    {
        Lead::create([
            'property_id' => $propertyInquiry->property_id,
            'property_inquiry_id' => $propertyInquiry->id,
            'name' => $propertyInquiry->name,
            'contact' => $propertyInquiry->email,
            'source' => 'website_inquiry',
            'status' => LeadStatus::New->value,
            'notes' => $propertyInquiry->message,
        ]);
    }
}
