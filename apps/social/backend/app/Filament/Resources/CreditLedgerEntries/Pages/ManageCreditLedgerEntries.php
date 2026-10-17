<?php

namespace App\Filament\Resources\CreditLedgerEntries\Pages;

use App\Filament\Resources\CreditLedgerEntries\CreditLedgerEntryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageCreditLedgerEntries extends ManageRecords
{
    protected static string $resource = CreditLedgerEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
