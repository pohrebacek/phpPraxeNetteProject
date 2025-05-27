<?php
namespace App\Module\Front\Presenters;

use Nette;

final class ShopPresenter extends BasePresenter {
    public function renderCart(): void {

    }

    public function handleSubmitCart(): void {

    }

    public function renderPremium(): void
    {
        $duration = $this->getParameter('duration') ?? null;
        bdump($duration);
        $this->template->duration = $duration;

    }

    public function handleSubmitPremium($duration) {
        $session = $this->getSession("cart");
        bdump($duration);

        $premiumMap = [
            '1m' => ['+1 month', 49],
            '3m' => ['+3 months', 129],
            '6m' => ['+6 months', 219],
            '12m' => ['+12 months', 399],
        ];

        if (!isset($premiumMap[$duration])) {
            $this->flashMessage('Neplatná délka předplatného.', 'error');
            $this->redirect('this');
        }

        /*  zkrácená verze oproti:
            $modify = $map[$duration][0];
            $price = $map[$duration][1];
        */
        [$modify, $price] = $premiumMap[$duration];

        $session->duration = $duration;
        $session->price = $price;
        $session->premiumUntil = (new \DateTimeImmutable())->modify($modify);

        bdump($session->premiumUntil);
        
        $this->flashMessage('Předplatné bylo přidáno do košíku.');
        $this->redirect('Shop:cart');
    }

}