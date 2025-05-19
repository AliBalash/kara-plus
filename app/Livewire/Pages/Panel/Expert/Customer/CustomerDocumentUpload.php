<?php

namespace App\Livewire\Pages\Panel\Expert\Customer;

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;
use App\Models\CustomerDocument;
use App\Models\Payment;
use Illuminate\Support\Str;


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


    public $hotel_name;
    public $hotel_address;

    public function mount($customerId, $contractId)
    {
        $this->customerId = $customerId;
        $this->contractId = $contractId;
        $customerDocument = CustomerDocument::where('customer_id', $this->customerId)->where('contract_id', $this->contractId)->first();

        // بررسی وجود اسناد مشتری
        $this->hasCustomerDocument = CustomerDocument::where('customer_id', $this->customerId)
            ->where('contract_id', $this->contractId)
            ->exists();

        // بررسی وجود پرداخت‌ها
        $this->hasPayments = Payment::where('customer_id', $this->customerId)
            ->where('contract_id', $this->contractId)
            ->exists();

        $visaPath = collect(['jpg', 'jpeg', 'png', 'pdf'])->map(function ($ext) {
            $path = "CustomerDocument/visa_{$this->customerId}_{$this->contractId}.{$ext}";
            return Storage::disk('myimage')->exists($path) ? Storage::url($path) : null;
        })->filter()->first();

        $passportPath = collect(['jpg', 'jpeg', 'png', 'pdf'])->map(function ($ext) {
            $path = "CustomerDocument/passport_{$this->customerId}_{$this->contractId}.{$ext}";
            return Storage::disk('myimage')->exists($path) ? Storage::url($path) : null;
        })->filter()->first();

        $licensePath = collect(['jpg', 'jpeg', 'png', 'pdf'])->map(function ($ext) {
            $path = "CustomerDocument/license_{$this->customerId}_{$this->contractId}.{$ext}";
            return Storage::disk('myimage')->exists($path) ? Storage::url($path) : null;
        })->filter()->first();

        $ticketPath = collect(['jpg', 'jpeg', 'png', 'pdf'])->map(function ($ext) {
            $path = "CustomerDocument/ticket_{$this->customerId}_{$this->contractId}.{$ext}";
            return Storage::disk('myimage')->exists($path) ? Storage::url($path) : null;
        })->filter()->first();

        if (!empty($customerDocument)) {

            $this->hotel_name = $customerDocument->hotel_name;
            $this->hotel_address = $customerDocument->hotel_address;
        }

        // بررسی وجود فایل‌های آپلود شده
        $this->existingFiles = [
            'visa' => $visaPath,
            'passport' => $passportPath,
            'license' => $licensePath,
            'ticket' => $ticketPath
        ];
    }


    public function uploadDocument()
    {

        // Dynamically build validation rules based on file inputs or existing files
        $validationRules = [
            'hotel_name' => 'required',
            'hotel_address' => 'required',

        ];

        // Visa file validation: check if file exists or is uploaded
        if ($this->visa) {
            // Apply validation only when a new file is uploaded
            $validationRules['visa'] = 'required|mimes:jpg,jpeg,png,pdf|max:8000';
        } elseif (!$this->visa && !$this->existingFiles['visa']) {
            // If no new file uploaded but existing file exists, no need for `required` rule
            $validationRules['visa'] = 'mimes:jpg,jpeg,png,pdf|max:8000';
        }

        // Passport file validation: same as visa logic
        if ($this->passport) {
            $validationRules['passport'] = 'required|mimes:jpg,jpeg,png,pdf|max:8000';
        } elseif (!$this->passport && !$this->existingFiles['passport']) {
            $validationRules['passport'] = 'mimes:jpg,jpeg,png,pdf|max:8000';
        }

        // License file validation: same as visa logic
        if ($this->license) {
            $validationRules['license'] = 'required|mimes:jpg,jpeg,png,pdf|max:8000';
        } elseif (!$this->license && !$this->existingFiles['license']) {
            $validationRules['license'] = 'mimes:jpg,jpeg,png,pdf|max:8000';
        }

        // Ticket file validation: same as visa logic
        if ($this->ticket) {
            $validationRules['ticket'] = 'required|mimes:jpg,jpeg,png,pdf|max:8000';
        } elseif (!$this->ticket && !$this->existingFiles['ticket']) {
            $validationRules['ticket'] = 'mimes:jpg,jpeg,png,pdf|max:8000';
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
            $extension = $this->visa->getClientOriginalExtension();
            $visaPath = $this->visa->storeAs('CustomerDocument', "visa_{$this->customerId}_{$this->contractId}.{$extension}", 'myimage');
            $customerDocument->license = $visaPath;
        }
        if ($this->passport) {
            $extension = $this->passport->getClientOriginalExtension();
            $passportPath = $this->passport->storeAs('CustomerDocument', "passport_{$this->customerId}_{$this->contractId}.{$extension}", 'myimage');
            $customerDocument->license = $passportPath;
        }
        if ($this->license) {
            $extension = $this->license->getClientOriginalExtension();
            $licensePath = $this->license->storeAs('CustomerDocument', "license_{$this->customerId}_{$this->contractId}.{$extension}", 'myimage');
            $customerDocument->license = $licensePath;
        }
        if ($this->ticket) {
            $extension = $this->ticket->getClientOriginalExtension();
            $ticketPath = $this->ticket->storeAs('CustomerDocument', "ticket_{$this->customerId}_{$this->contractId}.{$extension}", 'myimage');
            $customerDocument->ticket = $ticketPath;
        }

        $customerDocument->hotel_name = $this->hotel_name;
        $customerDocument->hotel_address = $this->hotel_address;



        $customerDocument->save();
        session()->flash('message', 'Documents uploaded successfully.');
        $this->mount($this->customerId, $this->contractId); // Refresh existing files
    }


    public function removeFile($fileType)
    {
        $pattern = "CustomerDocument/{$fileType}_{$this->customerId}_{$this->contractId}.";

        $files = Storage::disk('myimage')->files('CustomerDocument');

        foreach ($files as $file) {
            if (Str::startsWith($file, $pattern)) {
                $this->$fileType = '';
                Storage::disk('myimage')->delete($file);
                break;
            }
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
