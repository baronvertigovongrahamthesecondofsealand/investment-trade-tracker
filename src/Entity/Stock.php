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

    public function __construct()
    {
        $this->trades = new ArrayCollection();
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
    public function getTrades(): Collection
    {
        return $this->trades;
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
                $quantity += ($trade->getOrderType() == "Sell") ? -$trade->getQuantity() : $trade->getQuantity();
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

}
