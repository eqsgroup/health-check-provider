<?php

declare(strict_types=1);

namespace Ostrolucky\HealthCheckProvider\DTO;

use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;
use JsonSerializable;

use function array_filter;
use function str_contains;

/** @see https://datatracker.ietf.org/doc/html/draft-inadarei-api-health-check-06#name-the-checks-object */
class CheckDetails implements JsonSerializable
{
    /** @var string|int|float|array<mixed>|null */
    private string | int | float | array | null $observedValue = null;
    private Status $status = Status::healthy;
    private ?DateTimeImmutable $time = null;
    private ?string $output = null;

    /**
     * @param ?list<string> $affectedEndpoints
     * @param ?array<string, string> $links
     */
    public function __construct(
        private string $componentName,
        public readonly bool $isCritical,
        private ?array $links = null,
        private ?array $affectedEndpoints = null,
        private ?string $componentId = null,
        private ?string $componentType = null,
        private string | MeasurementName | null $measurementName = null,
        private ?string $observedUnit = null,
    ) {
        if (str_contains($this->componentName, ':')) {
            throw new InvalidArgumentException('Component name MUST NOT contain colon (":")');
        }
    }

    public function withMeasurementName(string | MeasurementName | null $measurementName): static
    {
        $that = clone $this;
        $that->measurementName = $measurementName;

        return $that;
    }

    /** @param float | int | array<mixed> | string | null $observedValue */
    public function withObservedValue(float | int | array | string | null $observedValue): static
    {
        $that = clone $this;
        $that->observedValue = $observedValue;

        return $that;
    }

    public function withObservedUnit(?string $observedUnit): static
    {
        $that = clone $this;
        $that->observedUnit = $observedUnit;

        return $that;
    }

    public function getStatus(): Status
    {
        return $this->status;
    }

    public function withStatus(Status $status): static
    {
        $that = clone $this;
        $that->status = $status;

        return $that;
    }

    public function withTime(?DateTimeImmutable $time): static
    {
        $that = clone $this;
        $that->time = $time;

        return $that;
    }

    public function getOutput(): ?string
    {
        return $this->output;
    }

    public function withOutput(?string $output): static
    {
        $that = clone $this;
        $that->output = $output;

        return $that;
    }

    /** @param ?array<string, string> $links */
    public function withLinks(?array $links): static
    {
        $that = clone $this;
        $that->links = $links;

        return $that;
    }

    public function getName(): string
    {
        if (!$measurement = $this->measurementName) {
            return $this->componentName;
        }

        if ($measurement instanceof MeasurementName) {
            $measurement = $measurement->value;
        }

        return "$this->componentName:$measurement";
    }

    /**
     * @inheritDoc
     * @phpstan-ignore-next-line
     */
    public function jsonSerialize(): array
    {
        return array_filter([
            'componentId' => $this->componentId,
            'componentType' => $this->componentType,
            'observedValue' => $this->observedValue,
            'observedUnit' => $this->observedUnit,
            'status' => $this->status,
            'affectedEndpoints' => $this->affectedEndpoints,
            'time' => $this->time?->format(DateTimeInterface::ATOM),
            'output' => $this->output,
            'links' => $this->links,
        ]);
    }
}
