<?php
namespace App\Module\Model\PremiumPurchase;

use Nette;
use Nette\Database\Table\ActiveRow;
use App\Module\Model\PremiumPurchase\PremiumPurchaseDTO;

class PremiumPurchaseMapper {
    public function __construct(

    ) {}

    public function map(ActiveRow $row): PremiumPurchaseDTO {
        return new PremiumPurchaseDTO($row->id, $row->user_id, $row->length, $row->price, $row->created_at);
    }
}