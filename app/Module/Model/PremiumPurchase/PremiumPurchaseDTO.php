<?php
namespace App\Module\Model\PremiumPurchase;

use Nette;

readonly class PremiumPurchaseDTO {
    function __construct(
        public mixed $id, public mixed $userId, public mixed $length, public mixed $price, public mixed $createdAt
    ) {
        
    }
}