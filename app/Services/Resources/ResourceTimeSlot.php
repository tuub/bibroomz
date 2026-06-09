<?php

namespace App\Services\Resources;

use Carbon\CarbonImmutable;

class ResourceTimeSlot
{
    public function __construct(
        public CarbonImmutable $time,
        public string $label,
        public bool $isDisabled = true,
        public bool $isSelected = false,
    ) {}

    public function withDisabled(bool $isDisabled): self
    {
        return new self($this->time, $this->label, $isDisabled, $this->isSelected);
    }

    public function withSelected(bool $isSelected): self
    {
        return new self($this->time, $this->label, $this->isDisabled, $isSelected);
    }

    /**
     * @return array{time: CarbonImmutable, label: string, is_disabled: bool, is_selected: bool}
     */
    public function toArray(): array
    {
        return [
            'time' => $this->time,
            'label' => $this->label,
            'is_disabled' => $this->isDisabled,
            'is_selected' => $this->isSelected,
        ];
    }
}
