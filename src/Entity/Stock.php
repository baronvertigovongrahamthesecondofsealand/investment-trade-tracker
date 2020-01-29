<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\StockRepository")
 */
class Stock
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $symbol;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $price = null;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $refreshedAt = null;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Trade", mappedBy="stock")
     */
    private $trades;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $stockType;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $longTarget;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $shortTarget;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $callTarget;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $nextEarningsAt;

    /**
     * @ORM\OneToMany(targetEntity="Transaction", mappedBy="stock")
     */
    private $transactions;

    public function __construct()
    {
        $this->trades = new ArrayCollection();
        $this->transactions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSymbol(): ?string
    {
        return $this->symbol;
    }

    public function setSymbol(string $symbol): self
    {
        $this->symbol = $symbol;

        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(string $price): self
    {
        $this->price = $price;

        return $this;
    }

    public function getRefreshedAt(): ?\DateTimeInterface
    {
        return $this->refreshedAt;
    }

    public function setRefreshedAt(\DateTimeInterface $refreshedAt): self
    {
        $this->refreshedAt = $refreshedAt;

        return $this;
    }

    /**
     * @return Collection|Trade[]
     */
    public function getTrades($type = null, $orderBy = null): Collection
    {
        $trades = $this->trades;

        if ($orderBy) {
            $orderByField   = isset($orderBy[0]) && $orderBy[0] ? $orderBy[0] : 'executedAt';
            $orderByDir     = isset($orderBy[1]) && $orderBy[1] != 'ASC' ? -1 : 1;

            $iterator = $this->trades->getIterator();
            $iterator->uasort(function ($a, $b) use ($orderByField, $orderByDir) {
                return call_user_func([$a, 'get'.$orderByField]) < call_user_func([$b, 'get'.$orderByField]) ? !$orderByDir : $orderByDir;
            });

            $trades = new ArrayCollection(iterator_to_array($iterator));
        }

        if ($type) {
            return $trades->filter(function($trade) use ($type) {
                return $trade->getTradeType() == $type;
            });
        }

        return $trades;
    }

    public function addTrade(Trade $trade): self
    {
        if (!$this->trades->contains($trade)) {
            $this->trades[] = $trade;
            $trade->setStock($this);
        }

        return $this;
    }

    public function removeTrade(Trade $trade): self
    {
        if ($this->trades->contains($trade)) {
            $this->trades->removeElement($trade);
            // set the owning side to null (unless already changed)
            if ($trade->getStock() === $this) {
                $trade->setStock(null);
            }
        }

        return $this;
    }

    public function getQuantity($tradeType) {
        $quantity = 0;

        foreach ($this->getTrades() as $trade) {
            if ($trade->getTradeType() == $tradeType) {
                $quantity += ($trade->getOrderType() == "Sell" || $trade->getOrderType() == "Expired") ? -$trade->getQuantity() : $trade->getQuantity();
            }
        }

        return $quantity;
    }

    public function getAdjustedPrice($tradeType) {
        $trades = $this->getTrades();

        /** @var Trade $oldest */
        $newest = $trades->first();

        foreach ($trades as $trade) {
            if ($trade->getTradeType() == $tradeType) {
                if ($trade->getExecutedAt() > $newest->getExecutedAt()) {
                    $newest = $trade;
                }
            }
        }

        return $newest->getAdjustedPrice();
    }

    public function getGain($tradeType) {
        if ($this->getPrice() <= 0) {
            $gain = 0;
        } elseif ($tradeType == 'Long' && $this->getAdjustedPrice('Long')) {
            $gain = (($this->getPrice() /$this->getAdjustedPrice('Long')) -1) *100;
        } elseif ($tradeType == 'Short' && $this->getAdjustedPrice('Short')) {
            $gain = (1- ($this->getPrice() /$this->getAdjustedPrice('Short'))) *100;
        } elseif ($tradeType == 'Option' && $this->getAdjustedPrice('Option')) {
            $gain = (($this->getPrice() /$this->getAdjustedPrice('Option')) -1) *100;
        } else {
            $gain = 0;
        }

        return round($gain,2);
    }

    public function getStockType(): ?string
    {
        return $this->stockType;
    }

    public function setStockType(string $stockType): self
    {
        $this->stockType = $stockType;

        return $this;
    }

    public function getLongTarget(): ?float
    {
        return $this->longTarget;
    }

    public function setLongTarget(?float $longTarget): self
    {
        $this->longTarget = $longTarget;

        return $this;
    }

    public function getShortTarget(): ?float
    {
        return $this->shortTarget;
    }

    public function setShortTarget(?float $shortTarget): self
    {
        $this->shortTarget = $shortTarget;

        return $this;
    }

    public function getCallTarget(): ?float
    {
        return $this->callTarget;
    }

    public function setCallTarget(?float $callTarget): self
    {
        $this->callTarget = $callTarget;

        return $this;
    }

    public function getNextEarningsAt(): ?\DateTimeInterface
    {
        return $this->nextEarningsAt;
    }

    public function setNextEarningsAt(?\DateTimeInterface $nextEarningsAt): self
    {
        $this->nextEarningsAt = $nextEarningsAt;

        return $this;
    }

    /**
     * @return Collection|Transaction[]
     */
    public function getTransactions(): Collection
    {
        return $this->transactions;
    }

    public function addTransaction(Transaction $transaction): self
    {
        if (!$this->transactions->contains($transaction)) {
            $this->transactions[] = $transaction;
            $transaction->setStock($this);
        }

        return $this;
    }

    public function removeTransaction(Transaction $transaction): self
    {
        if ($this->transactions->contains($transaction)) {
            $this->transactions->removeElement($transaction);
            // set the owning side to null (unless already changed)
            if ($transaction->getStock() === $this) {
                $transaction->setStock(null);
            }
        }

        return $this;
    }

}
