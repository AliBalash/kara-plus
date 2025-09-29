<?php

namespace App\Livewire\Pages\Panel\Expert\RentalRequest;

use App\Models\Contract;
use App\Models\Payment;
use App\Models\ReturnDocument;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\WithFileUploads;
use Livewire\Component;

class RentalRequestReturnDocument extends Component
{

    use WithFileUploads;

    public $contractId;
    public $factorContract;
    public $carDashboard;
    public $carVideoOutside;
    public $carVideoInside;
    public $fuelLevel  = 50;
    public $mileage;
    public $note;
    public $driverNote;
    public $existingFiles = [];

    public $remainingBalance;
    public $contract;


    public function mount($contractId)
    {
        $this->contractId = $contractId;
        $this->contract = Contract::findOrFail($contractId);

        $this->contractId = $contractId;
        $return = ReturnDocument::where('contract_id', $contractId)->first();
        if (!empty($return)) {
            $this->fuelLevel = $return->fuelLevel;
            $this->mileage = $return->mileage;
            $this->note = $return->note;
            $this->driverNote = $return->driver_note;
        }
        $this->existingFiles = [
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

        $this->remainingBalance = $this->contract->calculateRemainingBalance();
    }

    public function uploadDocuments()
    {
        $validationRules = [
            'fuelLevel' => 'required',
            'mileage' => 'required',
            'note' => 'nullable|string|max:1000',
            'driverNote' => 'nullable|string|max:1000',
        ];

        // Factor Contract Validation
        if ($this->factorContract) {
            $validationRules['factorContract'] = 'required|image|max:8048';
        } elseif (!$this->factorContract && empty($this->existingFiles['factorContract'])) {
            $validationRules['factorContract'] = 'image|max:8048';
        }



        // Car Dashboard  Validation
        if ($this->carDashboard) {
            $validationRules['carDashboard'] = 'required|image|max:8048';
        } elseif (!$this->carDashboard && empty($this->existingFiles['carDashboard'])) {
            $validationRules['carDashboard'] = 'image|max:8048';
        }


        // Car Video Inside Validation
        if ($this->carVideoInside) {
            $validationRules['carVideoInside'] = 'required|max:20240';
        } elseif (!$this->carVideoInside && empty($this->existingFiles['carVideoInside'])) {
            $validationRules['carVideoInside'] = 'max:20240';
        }

        // Car Video Outside Validation
        if ($this->carVideoOutside) {
            $validationRules['carVideoOutside'] = 'required|max:20240';
        } elseif (!$this->carVideoOutside && empty($this->existingFiles['carVideoOutside'])) {
            $validationRules['carVideoOutside'] = 'max:20240';
        }



        if ($validationRules) {
            $this->validate($validationRules);
        }


        // Start Database Transaction
        DB::beginTransaction();
        $uploadedPaths = [];

        try {
            $returnDocument = ReturnDocument::updateOrCreate(
                ['contract_id' => $this->contractId],
                ['user_id' => auth()->id()]
            );
            $returnDocument->fuelLevel = $this->fuelLevel;
            $returnDocument->mileage = $this->mileage;
            $returnDocument->note = $this->note;
            $returnDocument->driver_note = $this->driverNote;

            // Factor Contract Upload
            if ($this->factorContract) {
                $factorPath = $this->factorContract->storeAs('ReturnDocument', "factor_contract_{$this->contractId}.jpg", 'myimage');
                if (!$factorPath) throw new \Exception('Error uploading factor contract.');
                $returnDocument->factor_contract = $factorPath;
                $uploadedPaths[] = $factorPath;
            }


            // Car Dashboard  Upload
            if ($this->carDashboard) {
                $carDashboardPath = $this->carDashboard->storeAs('ReturnDocument', "car_dashboard_{$this->contractId}.jpg", 'myimage');
                if (!$carDashboardPath) throw new \Exception('Error uploading dashboard photo.');
                $returnDocument->car_dashboard = $carDashboardPath;
                $uploadedPaths[] = $carDashboardPath;
            }

            // Car Video Inside Upload
            if ($this->carVideoInside) {
                $videoPath = $this->carVideoInside->storeAs('ReturnDocument', "car_video_inside_{$this->contractId}.mp4", 'myimage');
                if (!$videoPath) throw new \Exception('Error uploading car inside video.');
                $returnDocument->car_inside_video = $videoPath;
                $uploadedPaths[] = $videoPath;
            }

            // Car Video Outside Upload
            if ($this->carVideoOutside) {
                $videoPath = $this->carVideoOutside->storeAs('ReturnDocument', "car_video_outside_{$this->contractId}.mp4", 'myimage');
                if (!$videoPath) throw new \Exception('Error uploading car video.');
                $returnDocument->car_outside_video = $videoPath;
                $uploadedPaths[] = $videoPath;
            }
            $returnDocument->user_id = auth()->id();
            $returnDocument->save();



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
        // نگاشت بین fileType از view و نام واقعی فیلد در دیتابیس + نوع فایل
        $mapping = [
            'tars_contract' => ['db_field' => 'tars_contract', 'extension' => 'jpg'],
            'kardo_contract' => ['db_field' => 'kardo_contract', 'extension' => 'jpg'],
            'factor_contract' => ['db_field' => 'factor_contract', 'extension' => 'jpg'],
            'car_dashboard' => ['db_field' => 'car_dashboard', 'extension' => 'jpg'],
            'car_video_outside' => ['db_field' => 'car_outside_video', 'extension' => 'mp4'],
            'car_video_inside' => ['db_field' => 'car_inside_video', 'extension' => 'mp4'],
        ];

        // بررسی اعتبار کلید
        if (!array_key_exists($fileType, $mapping)) {
            session()->flash('error', 'The file type "' . $fileType . '" is not valid.');
            return;
        }

        $dbField = $mapping[$fileType]['db_field'];
        $extension = $mapping[$fileType]['extension'];

        // ساخت مسیر فایل
        $filePath = "ReturnDocument/{$fileType}_{$this->contractId}.{$extension}";

        if (Storage::disk('myimage')->exists($filePath)) {
            Storage::disk('myimage')->delete($filePath);
        }

        // پاک کردن مقدار از دیتابیس
        $returnDocument = ReturnDocument::where('contract_id', $this->contractId)->first();
        if ($returnDocument) {
            $returnDocument->$dbField = null;
            $returnDocument->save();
        }

        // پاک‌سازی فایل در UI
        $this->existingFiles[$fileType] = null;

        session()->flash('message', ucfirst($fileType) . ' successfully removed.');

        // بارگذاری مجدد
        $this->mount($this->contractId);
    }


    public function render()
    {
        return view('livewire.pages.panel.expert.rental-request.rental-request-return-document');
    }


    public function changeStatusToPayment($contractId)
    {
        $contract = Contract::findOrFail($contractId);

        // اگر کنترکت قبلاً وضعیت payment دارد، کاری نکن
        if ($contract->current_status === 'payment') {
            session()->flash('message', 'Contract is already in payment status.');
            return;
        }

        DB::beginTransaction();
        try {

            $contract->changeStatus('returned', auth()->id());
            // سپس به payment
            $contract->changeStatus('payment', auth()->id());
            DB::commit();
            session()->flash('message', 'Status changed to Returned then Payment successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Error changing status: ' . $e->getMessage());
        }
    }
}
