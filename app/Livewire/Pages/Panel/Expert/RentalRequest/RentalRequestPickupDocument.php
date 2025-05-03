<?php

namespace App\Livewire\Pages\Panel\Expert\RentalRequest;

use App\Models\Contract;
use App\Models\PickupDocument;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class RentalRequestPickupDocument extends Component
{

    use WithFileUploads;

    public $contractId;
    public $tarsContract;
    public $kardoContract;
    public $factorContract;
    public $carVideo;
    public $existingFiles = [];



    public function mount($contractId)
    {
        $this->contractId = $contractId;
        $this->existingFiles = [
            'tarsContract' => Storage::disk('public')->exists("PickupDocument/tars_contract_{$this->contractId}.jpg")
                ? Storage::url("PickupDocument/tars_contract_{$this->contractId}.jpg")
                : null,
            'kardoContract' => Storage::disk('public')->exists("PickupDocument/kardo_contract_{$this->contractId}.jpg")
                ? Storage::url("PickupDocument/kardo_contract_{$this->contractId}.jpg")
                : null,
            'factorContract' => Storage::disk('public')->exists("PickupDocument/factor_contract_{$this->contractId}.jpg")
                ? Storage::url("PickupDocument/factor_contract_{$this->contractId}.jpg")
                : null,
            'carVideo' => Storage::disk('public')->exists("PickupDocument/car_video_{$this->contractId}.mp4")
                ? Storage::url("PickupDocument/car_video_{$this->contractId}.mp4")
                : null,
        ];
    }

    public function uploadDocuments()
    {
        $validationRules = [];

        // Tars Contract Validation
        if ($this->tarsContract) {
            $validationRules['tarsContract'] = 'required|image|max:2048';
        } elseif (!$this->tarsContract && empty($this->existingFiles['tarsContract'])) {
            $validationRules['tarsContract'] = 'image|max:2048';
        }

        // Kardo Contract Validation
        if ($this->kardoContract) {
            $validationRules['kardoContract'] = 'required|image|max:2048';
        } elseif (!$this->kardoContract && empty($this->existingFiles['kardoContract'])) {
            $validationRules['kardoContract'] = 'image|max:2048';
        }

        // Factor Contract Validation
        if ($this->factorContract) {
            $validationRules['factorContract'] = 'required|image|max:2048';
        } elseif (!$this->factorContract && empty($this->existingFiles['factorContract'])) {
            $validationRules['factorContract'] = 'image|max:2048';
        }

        // Car Video Validation
        if ($this->carVideo) {
            $validationRules['carVideo'] = 'required|mimetypes:video/mp4|max:10240';
        } elseif (!$this->carVideo && empty($this->existingFiles['carVideo'])) {
            $validationRules['carVideo'] = 'mimetypes:video/mp4|max:10240';
        }

        if ($validationRules) {
            $this->validate($validationRules);
        }

        // Start Database Transaction
        DB::beginTransaction();
        $uploadedPaths = [];

        try {
            $pickupDocument = PickupDocument::updateOrCreate(
                ['contract_id' => $this->contractId],
                ['user_id' => auth()->id()]
            );


            // Tars Contract Upload
            if ($this->tarsContract) {
                $tarsPath = $this->tarsContract->storeAs('PickupDocument', "tars_contract_{$this->contractId}.jpg", 'public');
                if (!$tarsPath) throw new \Exception('Error uploading Tars contract.');
                $pickupDocument->tars_contract = $tarsPath;
                $uploadedPaths[] = $tarsPath;
            }

            // Kardo Contract Upload
            if ($this->kardoContract) {
                $kardoPath = $this->kardoContract->storeAs('PickupDocument', "kardo_contract_{$this->contractId}.jpg", 'public');
                if (!$kardoPath) throw new \Exception('Error uploading Kardo contract.');
                $pickupDocument->kardo_contract = $kardoPath;
                $uploadedPaths[] = $kardoPath;
            }

            // Factor Contract Upload
            if ($this->factorContract) {
                $factorPath = $this->factorContract->storeAs('PickupDocument', "factor_contract_{$this->contractId}.jpg", 'public');
                if (!$factorPath) throw new \Exception('Error uploading factor contract.');
                $pickupDocument->factor_contract = $factorPath;
                $uploadedPaths[] = $factorPath;
            }

            // Car Video Upload
            if ($this->carVideo) {
                $videoPath = $this->carVideo->storeAs('PickupDocument', "car_video_{$this->contractId}.mp4", 'public');
                if (!$videoPath) throw new \Exception('Error uploading car video.');
                $pickupDocument->car_video = $videoPath;
                $uploadedPaths[] = $videoPath;
            }
            $pickupDocument->user_id = auth()->id();
            $pickupDocument->save();



            DB::commit();

            session()->flash('message', 'Documents uploaded successfully.');
            $this->mount($this->contractId);
        } catch (\Exception $e) {
            DB::rollBack();

            // حذف فایل‌های آپلود شده در صورت خطا
            foreach ($uploadedPaths as $path) {
                Storage::disk('public')->delete($path);
            }

            session()->flash('error', 'Error uploading documents: ' . $e->getMessage());
        }
    }


    public function removeFile($fileType)
    {
        $extension = ($fileType === 'car_video') ? 'mp4' : 'jpg';
        $filePath = "PickupDocument/{$fileType}_{$this->contractId}.{$extension}";

        if (Storage::disk('public')->exists($filePath)) {
            Storage::disk('public')->delete($filePath);
        }

        $this->existingFiles[$fileType] = null;

        $pickupDocument = PickupDocument::where('contract_id', $this->contractId)->first();
        if ($pickupDocument) {
            $pickupDocument->{$fileType} = null;
            $pickupDocument->save();
        }

        session()->flash('message', ucfirst($fileType) . ' successfully removed.');

        $this->mount($this->contractId);
    }

    public function render()
    {
        return view('livewire.pages.panel.expert.rental-request.rental-request-pickup-document');
    }

    public function changeStatusToDelivery($contractId)
    {
        $contract = Contract::findOrFail($contractId);

        // تغییر وضعیت به 'delivery'
        $contract->changeStatus('delivery', auth()->id());

        session()->flash('message', 'Status changed to Delivery successfully.');
    }


    public function changeStatusToAwaitingReturn($contractId)
    {
        $contract = Contract::findOrFail($contractId);

        // تغییر وضعیت به 'delivery'
        $contract->changeStatus('awaiting_return', auth()->id());

        session()->flash('message', 'Status changed to Kardo Tars successfully.');
    }
}
