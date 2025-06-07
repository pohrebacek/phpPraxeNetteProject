<?php
namespace App\Module\Model\PremiumPurchase;

use Nette;
use App\Module\Model\Base\BaseRepository;
use Nette\Database\Table\ActiveRow;

final class PremiumPurchaseRepository extends BaseRepository
{
    public function __construct(
        protected Nette\Database\Explorer $database,
    ) {
        $this->table = "premium_purchases";
    }

    
}