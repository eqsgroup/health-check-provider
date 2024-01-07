<?php

declare(strict_types=1);

namespace Ostrolucky\HealthCheckProvider\DTO;

use JsonSerializable;

use function array_filter;

/** @see https://datatracker.ietf.org/doc/html/draft-inadarei-api-health-check-06#name-api-health-response */
class HealthResponse implements JsonSerializable
{
    private Status $status = Status::healthy;
    /** @var ?list<string> */
    private ?array $notes = null;
    private ?string $output = null;
    /** @var ?array<string, CheckDetails> */
    private ?array $checks = null;

    public function __construct(
        private ?string $version = null,
        private ?string $releaseId = null,
        /** @var ?array<string, string> */
        private ?array $links = null,
        private ?string $serviceId = null,
        private ?string $description = null,
    ) {
    }

    public function withStatus(Status $status): static
    {
        $that = clone $this;
        $that->status = $status;

        return $that;
    }

    /** @param ?list<string> $notes */
    public function withNotes(?array $notes): static
    {
        $that = clone $this;
        $that->notes = $notes;

        return $that;
    }

    public function withOutput(?string $output): static
    {
        $that = clone $this;
        $that->output = $output;

        return $that;
    }

    /** @param ?array<string, CheckDetails> $checks */
    public function withChecks(?array $checks): static
    {
        $that = clone $this;
        $that->checks = $checks;

        return $that;
    }

    /**
     * @inheritDoc
     * @phpstan-ignore-next-line
     */
    public function jsonSerialize(): array
    {
        return array_filter(
            [
                'status' => $this->status,
                'version' => $this->version,
                'releaseId' => $this->releaseId,
                'notes' => $this->notes,
                'output' => $this->output,
                'checks' => $this->checks,
                'links' => $this->links,
                'serviceId' => $this->serviceId,
                'description' => $this->description,
            ],
            fn (mixed $value) => $value !== null,
        );
    }
}
