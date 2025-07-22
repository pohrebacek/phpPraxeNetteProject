<?php
namespace App\Module\Model\PremiumPurchase;

use DateTime;
use Nette;

readonly class PremiumPurchaseDTO {
    function __construct(
        public int $id, public int $userId, public string $length, public int $price, public DateTime $createdAt
    ) {
        
    }
}