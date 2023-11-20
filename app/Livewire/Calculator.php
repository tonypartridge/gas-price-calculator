<?php

namespace App\Livewire;

use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Livewire\Component;

class Calculator extends Component implements HasForms
{
    
    use InteractsWithForms;
    
    public $previousReading;
    public $currentReading;
    public $vcf;
    public $kwcf;
    public $dailyStandingCharge;
    public $startDate;
    public $endDate;
    public $costPerKW;
    
    public function mount() {
        $this->form->fill();
    }
    
    public function render()
    {
        return view('livewire.calculator');
    }
    
    public function form(Form $form): Form
    {
        return $form
            ->columns(2)
            ->schema([
                TextInput::make('previousReading')
                    ->label('Previous Reading')
                    ->required()
                    ->default(9170)
                    ->live()
                    ->numeric(),
                TextInput::make('currentReading')
                    ->label('Current Reading')
                    ->required()
                    ->default(9227)
                    ->live()
                    ->numeric(),
                TextInput::make('vcf')
                    ->label('Volume Correction Factor')
                    ->required()
                    ->default(1.02264)
                    ->live()
                    ->numeric(),
                TextInput::make('kwcf')
                    ->label('Kilowatt Correction Factor')
                    ->required()
                    ->default(3.6)
                    ->live()
                    ->numeric(),
                TextInput::make('dailyStandingCharge')
                    ->label('Daily Standing Charge')
                    ->required()
                    ->default(0.160912)
                    ->live()
                    ->numeric(),
                DatePicker::make('startDate')
                    ->label('Start Date')
                    ->live()
                    ->default( \Carbon\Carbon::now()->subMonth()->startOfMonth()->format('d-m-Y') ) // Start of last month
                    ->required(),
                DatePicker::make('endDate')
                    ->label('End Date')
                    ->live()
                    ->default(\Carbon\Carbon::now()->startOfMonth()->format('d-m-Y'))
                    ->required(),
                TextInput::make('costPerKW')
                    ->label('Cost Per KW')
                    ->live()
                    ->default(0.133153)
                    ->required()
                    ->numeric(),
                
                Placeholder::make('placeholder')
                    ->label('Result')
                    ->live()
                    ->content(fn($get) => 'Cost Estimate: £' . self::calculateUKGas($get)['gasCost']  . ' test')
            ]);
    }
    
    public static function calculateUKGas($get) {
        
        // Calculate the gas used in kWhs
        $gasUsed = ((int) $get('currentReading') - (int) $get('previousReading'));
        $gas_kwHsUsed =  $gasUsed * (float) $get('vcf') * 40.16824 / (float) $get('kwcf');;
        
        // Build a date difference with carbon
        $startDateTime = Carbon::createFromFormat('Y-m-d', $get('startDate'));
        $endDateTime = Carbon::createFromFormat('Y-m-d', $get('endDate'));
        $days = $endDateTime->diffInDays($startDateTime);
        
        $standardCharge = (float) $get('dailyStandingCharge') * $days;
        
        // Calculate the total cost
        $cost = ($gas_kwHsUsed * $get('costPerKW')) + ($standardCharge);
        
        return [
            'gasCost' => number_format($cost, 2),
            'standardCharge' => $standardCharge,
            'gasUsed' => $gasUsed,
            'gas_kwHsUsed' => $gas_kwHsUsed,
            'days' => $days,
            'previousReadingDate' => date('d/m/Y', $startDateTime->getTimestamp()),
            'currentReadingDate' => date('d/m/Y', $endDateTime->getTimestamp()),
            'previousReading' => $get('previousReading'),
            'currentReading' => $get('currentReading'),
        ];
    }
}
