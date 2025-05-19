<?php

namespace App\Livewire\Pages\Panel\Expert\RentalRequest;

use App\Models\Contract;
use App\Models\ReturnDocument;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\WithFileUploads;
use Livewire\Component;

class RentalRequestReturnDocument extends Component
{

    use WithFileUploads;

    public $contractId;
    public $tarsContract;
    public $kardoContract;
    public $factorContract;
    public $carDashboard;
    public $carVideoOutside;
    public $carVideoInside;
    public $fuelLevel  = 50;
    public $mileage;
    public $existingFiles = [];



    public function mount($contractId)
    {
        $this->contractId = $contractId;
        $pickup = ReturnDocument::where('contract_id', $contractId)->first();
        if (!empty($pickup)) {
            $this->fuelLevel = $pickup->fuelLevel;
            $this->mileage = $pickup->mileage;
        }
        $this->existingFiles = [
            'tarsContract' => Storage::disk('myimage')->exists("ReturnDocument/tars_contract_{$this->contractId}.jpg")
                ? Storage::url("ReturnDocument/tars_contract_{$this->contractId}.jpg")
                : null,
            'kardoContract' => Storage::disk('myimage')->exists("ReturnDocument/kardo_contract_{$this->contractId}.jpg")
                ? Storage::url("ReturnDocument/kardo_contract_{$this->contractId}.jpg")
                : null,
            'factorContract' => Storage::disk('myimage')->exists("ReturnDocument/factor_contract_{$this->contractId}.jpg")
                ? Storage::url("ReturnDocument/factor_contract_{$this->contractId}.jpg")
                : null,
            'carDashboard' => Storage::disk('myimage')->exists("ReturnDocument/car_dashboard_{$this->contractId}.jpg")
                ? Storage::url("ReturnDocument/car_dashboard_{$this->contractId}.jpg")
                : null,
            'carVideoOutside' => Storage::disk('myimage')->exists("ReturnDocument/car_video_outside_{$this->contractId}.mp4")
                ? Storage::url("ReturnDocument/car_video_outside_{$this->contractId}.mp4")
                : null,
            'carVideoInside' => Storage::disk('myimage')->exists("ReturnDocument/car_video_inside_{$this->contractId}.mp4")
                ? Storage::url("ReturnDocument/car_video_inside_{$this->contractId}.mp4")
                : null,
        ];
    }

    public function uploadDocuments()
    {
        $validationRules = [
            'fuelLevel' => 'required',
            'mileage' => 'required',
        ];

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



        // Car Dashboard  Validation
        if ($this->carDashboard) {
            $validationRules['carDashboard'] = 'required|image|max:2048';
        } elseif (!$this->carDashboard && empty($this->existingFiles['carDashboard'])) {
            $validationRules['carDashboard'] = 'image|max:2048';
        }


        // Car Video Inside Validation
        if ($this->carVideoInside) {
            $validationRules['carVideoInside'] = 'required|mimetypes:video/mp4|max:10240';
        } elseif (!$this->carVideoInside && empty($this->existingFiles['carVideoInside'])) {
            $validationRules['carVideoInside'] = 'mimetypes:video/mp4|max:10240';
        }

        // Car Video Outside Validation
        if ($this->carVideoOutside) {
            $validationRules['carVideoOutside'] = 'required|mimetypes:video/mp4|max:10240';
        } elseif (!$this->carVideoOutside && empty($this->existingFiles['carVideoOutside'])) {
            $validationRules['carVideoOutside'] = 'mimetypes:video/mp4|max:10240';
        }



        if ($validationRules) {
            $this->validate($validationRules);
        }


        // Start Database Transaction
        DB::beginTransaction();
        $uploadedPaths = [];

        try {
            $pickupDocument = ReturnDocument::updateOrCreate(
                ['contract_id' => $this->contractId],
                ['user_id' => auth()->id()]
            );
            $pickupDocument->fuelLevel = $this->fuelLevel;
            $pickupDocument->mileage = $this->mileage;

            // Tars Contract Upload
            if ($this->tarsContract) {
                $tarsPath = $this->tarsContract->storeAs('ReturnDocument', "tars_contract_{$this->contractId}.jpg", 'myimage');
                if (!$tarsPath) throw new \Exception('Error uploading Tars contract.');
                $pickupDocument->tars_contract = $tarsPath;
                $uploadedPaths[] = $tarsPath;
            }

            // Kardo Contract Upload
            if ($this->kardoContract) {
                $kardoPath = $this->kardoContract->storeAs('ReturnDocument', "kardo_contract_{$this->contractId}.jpg", 'myimage');
                if (!$kardoPath) throw new \Exception('Error uploading Kardo contract.');
                $pickupDocument->kardo_contract = $kardoPath;
                $uploadedPaths[] = $kardoPath;
            }

            // Factor Contract Upload
            if ($this->factorContract) {
                $factorPath = $this->factorContract->storeAs('ReturnDocument', "factor_contract_{$this->contractId}.jpg", 'myimage');
                if (!$factorPath) throw new \Exception('Error uploading factor contract.');
                $pickupDocument->factor_contract = $factorPath;
                $uploadedPaths[] = $factorPath;
            }


            // Car Dashboard  Upload
            if ($this->carDashboard) {
                $carDashboardPath = $this->carDashboard->storeAs('ReturnDocument', "car_dashboard_{$this->contractId}.jpg", 'myimage');
                if (!$carDashboardPath) throw new \Exception('Error uploading dashboard photo.');
                $pickupDocument->car_dashboard = $carDashboardPath;
                $uploadedPaths[] = $carDashboardPath;
            }

            // Car Video Inside Upload
            if ($this->carVideoInside) {
                $videoPath = $this->carVideoInside->storeAs('ReturnDocument', "car_video_inside_{$this->contractId}.mp4", 'myimage');
                if (!$videoPath) throw new \Exception('Error uploading car inside video.');
                $pickupDocument->car_inside_video = $videoPath;
                $uploadedPaths[] = $videoPath;
            }

            // Car Video Outside Upload
            if ($this->carVideoOutside) {
                $videoPath = $this->carVideoOutside->storeAs('ReturnDocument', "car_video_outside_{$this->contractId}.mp4", 'myimage');
                if (!$videoPath) throw new \Exception('Error uploading car video.');
                $pickupDocument->car_outside_video = $videoPath;
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
                Storage::disk('myimage')->delete($path);
            }

            session()->flash('error', 'Error uploading documents: ' . $e->getMessage());
        }
    }


    public function removeFile($fileType)
    {
        $videoFields = ['car_video_outside', 'car_video_inside'];
        $extension = in_array($fileType, $videoFields) ? 'mp4' : 'jpg';
        $filePath = "ReturnDocument/{$fileType}_{$this->contractId}.{$extension}";
        if (Storage::disk('myimage')->exists($filePath)) {
            Storage::disk('myimage')->delete($filePath);
        }

        $this->existingFiles[$fileType] = null;

        $pickupDocument = ReturnDocument::where('contract_id', $this->contractId)->first();
        if ($pickupDocument) {
            $pickupDocument->{$fileType} = null;
            $pickupDocument->save();
        }

        session()->flash('message', ucfirst($fileType) . ' successfully removed.');

        $this->mount($this->contractId);
    }

    public function render()
    {
        return view('livewire.pages.panel.expert.rental-request.rental-request-return-document');
    }


    public function changeStatusToPayment($contractId)
    {
        $contract = Contract::findOrFail($contractId);

        // تغییر وضعیت به 'awaiting_return'
        $contract->changeStatus('payment', auth()->id());

        session()->flash('message', 'Status changed to payment successfully.');
    }
}
