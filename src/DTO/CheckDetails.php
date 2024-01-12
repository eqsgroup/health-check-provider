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
    private string | MeasurementName | null $measurementName = null;
    private ?string $componentId = null;
    private ?string $componentType = null;
    /** @var string|int|float|array<mixed>|null */
    private string | int | float | array | null $observedValue = null;
    private ?string $observedUnit = null;
    private Status $status = Status::healthy;
    /** @var ?list<string> */
    private ?array $affectedEndpoints = null;
    private ?DateTimeImmutable $time = null;
    private ?string $output = null;

    /** @param ?array<string, string> $links */
    public function __construct(
        private string $componentName,
        public readonly bool $isCritical,
        private ?array $links = null,
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

    public function withComponentId(?string $componentId): static
    {
        $that = clone $this;
        $that->componentId = $componentId;

        return $that;
    }

    public function withComponentType(?string $componentType): static
    {
        $that = clone $this;
        $that->componentType = $componentType;

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

    /** @param ?list<string> $affectedEndpoints */
    public function withAffectedEndpoints(?array $affectedEndpoints): static
    {
        $that = clone $this;
        $that->affectedEndpoints = $affectedEndpoints;

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
