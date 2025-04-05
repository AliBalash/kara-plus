<?php

namespace App\Livewire\Pages\Panel\Expert\Customer;

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;
use App\Models\CustomerDocument;
use App\Models\Payment;

class CustomerDocumentUpload extends Component
{
    use WithFileUploads;

    public $customerId;
    public $contractId;
    public $visa;
    public $passport;
    public $license;
    public $ticket;
    public $hasCustomerDocument;
    public $hasPayments;
    public $existingFiles = [];

    public function mount($customerId, $contractId)
    {
        $this->customerId = $customerId;
        $this->contractId = $contractId;

        // بررسی وجود اسناد مشتری
        $this->hasCustomerDocument = CustomerDocument::where('customer_id', $this->customerId)
            ->where('contract_id', $this->contractId)
            ->exists();

        // بررسی وجود پرداخت‌ها
        $this->hasPayments = Payment::where('customer_id', $this->customerId)
            ->where('contract_id', $this->contractId)
            ->exists();

        // بررسی وجود فایل‌های آپلود شده
        $this->existingFiles = [
            'visa' => Storage::disk('public')->exists("CustomerDocument/visa_{$this->customerId}_{$this->contractId}.jpg")
                ? Storage::url("CustomerDocument/visa_{$this->customerId}_{$this->contractId}.jpg")
                : null,
            'passport' => Storage::disk('public')->exists("CustomerDocument/passport_{$this->customerId}_{$this->contractId}.jpg")
                ? Storage::url("CustomerDocument/passport_{$this->customerId}_{$this->contractId}.jpg")
                : null,
            'license' => Storage::disk('public')->exists("CustomerDocument/license_{$this->customerId}_{$this->contractId}.jpg")
                ? Storage::url("CustomerDocument/license_{$this->customerId}_{$this->contractId}.jpg")
                : null,
            'ticket' => Storage::disk('public')->exists("CustomerDocument/ticket_{$this->customerId}_{$this->contractId}.jpg")
                ? Storage::url("CustomerDocument/ticket_{$this->customerId}_{$this->contractId}.jpg")
                : null,
        ];
    }


    public function uploadDocument()
    {

        // Dynamically build validation rules based on file inputs or existing files
        $validationRules = [];

        // Visa file validation: check if file exists or is uploaded
        if ($this->visa) {
            // Apply validation only when a new file is uploaded
            $validationRules['visa'] = 'required|image|max:2048';
        } elseif (!$this->visa && !$this->existingFiles['visa']) {
            // If no new file uploaded but existing file exists, no need for `required` rule
            $validationRules['visa'] = 'image|max:2048';
        }

        // Passport file validation: same as visa logic
        if ($this->passport) {
            $validationRules['passport'] = 'required|image|max:2048';
        } elseif (!$this->passport && !$this->existingFiles['passport']) {
            $validationRules['passport'] = 'image|max:2048';
        }

        // License file validation: same as visa logic
        if ($this->license) {
            $validationRules['license'] = 'required|image|max:2048';
        } elseif (!$this->license && !$this->existingFiles['license']) {
            $validationRules['license'] = 'image|max:2048';
        }

        // Ticket file validation: same as visa logic
        if ($this->ticket) {
            $validationRules['ticket'] = 'required|image|max:2048';
        } elseif (!$this->ticket && !$this->existingFiles['ticket']) {
            $validationRules['ticket'] = 'image|max:2048';
        }

        // Validate based on the dynamically computed validation rules
        if ($validationRules) {
            $this->validate($validationRules);
        }


        // Update or create a customer document record
        $customerDocument = CustomerDocument::updateOrCreate(
            ['customer_id' => $this->customerId, 'contract_id' => $this->contractId],
            []
        );

        // Store the uploaded files
        if ($this->visa) {
            $visaPath = $this->visa->storeAs('CustomerDocument', "visa_{$this->customerId}_{$this->contractId}.jpg", 'public');
            $customerDocument->visa = $visaPath;
        }
        if ($this->passport) {
            $passportPath = $this->passport->storeAs('CustomerDocument', "passport_{$this->customerId}_{$this->contractId}.jpg", 'public');
            $customerDocument->passport = $passportPath;
        }
        if ($this->license) {
            $licensePath = $this->license->storeAs('CustomerDocument', "license_{$this->customerId}_{$this->contractId}.jpg", 'public');
            $customerDocument->license = $licensePath;
        }
        if ($this->ticket) {
            $ticketPath = $this->ticket->storeAs('CustomerDocument', "ticket_{$this->customerId}_{$this->contractId}.jpg", 'public');
            $customerDocument->ticket = $ticketPath;
        }

        $customerDocument->save();
        session()->flash('message', 'Documents uploaded successfully.');
        $this->mount($this->customerId, $this->contractId); // Refresh existing files
    }


    public function removeFile($fileType)
    {
        $filePath = "CustomerDocument/{$fileType}_{$this->customerId}_{$this->contractId}.jpg";

        if (Storage::disk('public')->exists($filePath)) {
            Storage::disk('public')->delete($filePath);
        }

        $this->existingFiles[$fileType] = null;

        $customerDocument = CustomerDocument::where('customer_id', $this->customerId)
            ->where('contract_id', $this->contractId)
            ->first();
        if ($customerDocument) {
            $customerDocument->{$fileType} = null;
            $customerDocument->save();
        }

        session()->flash('message', ucfirst($fileType) . ' successfully removed.');
    }

    public function render()
    {
        return view('livewire.pages.panel.expert.customer.customer-document-upload');
    }
}
