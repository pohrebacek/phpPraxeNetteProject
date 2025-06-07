<?php
namespace App\Module\Model\PremiumPurchase;

use Nette;
use App\Module\Model\PremiumPurchase\PremiumPurchaseDTO;
use App\Module\Model\PremiumPurchase\PremiumPurchaseRepository;
use App\Module\Model\PremiumPurchase\PremiumPurchaseMapper;

final class PremiumPurchaseFacade
{
    private function __construct(
        protected Nette\Database\Explorer $database,
        private PremiumPurchaseRepository $premiumPurchasesRepository,
        private PremiumPurchaseMapper $premiumPurchaseMapper
    ) {

    }

    public function getPremiumPurchaseDTO(int $id): PremiumPurchaseDTO
    {
        $premiumPurchaseRow = $this->premiumPurchasesRepository->getRowById($id);
        return $this->premiumPurchaseMapper->map($premiumPurchaseRow);
    }
}