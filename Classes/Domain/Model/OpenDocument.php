<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace TYPO3\CMS\Opendocs\Domain\Model;

/**
 * Value object representing an open document in the backend.
 *
 * An open document represents a record being edited in the FormEngine,
 * displayed in the "open documents" toolbar.
 *
 * Identified uniquely by table:uid combination.
 *
 * @internal
 */
readonly class OpenDocument implements \JsonSerializable
{
    public function __construct(
        public string $table,
        public int $uid,
        public \DateTimeImmutable $updatedAt,
    ) {}

    /**
     * Get the identifier for this document (table:uid).
     */
    public function getIdentifier(): string
    {
        return $this->table . ':' . $this->uid;
    }

    /**
     * Create from the legacy array format stored in session.
     *
     * Legacy format: [0 => title, 1 => params, 2 => queryString, 3 => metadata, 4 => returnUrl]
     */
    public static function fromLegacyArray(array $data): self
    {
        $params = $data[1] ?? [];
        $metadata = $data[3] ?? [];

        // Extract table and uid from params (edit configuration)
        // Params structure: ['edit' => ['table_name' => [uid => 'edit']]]
        $table = '';
        $uid = 0;

        if (isset($params['edit']) && is_array($params['edit'])) {
            $table = (string)array_key_first($params['edit']);
            if ($table && is_array($params['edit'][$table])) {
                $uid = (int)array_key_first($params['edit'][$table]);
            }
        }

        // Fallback to metadata if params didn't yield results
        if (!$table && isset($metadata['table'])) {
            $table = $metadata['table'];
        }
        if (!$uid && isset($metadata['uid'])) {
            $uid = (int)$metadata['uid'];
        }

        return new self(
            table: $table,
            uid: $uid,
            updatedAt: isset($metadata['updatedAt']) ? new \DateTimeImmutable($metadata['updatedAt']) : new \DateTimeImmutable(),
        );
    }

    public static function fromArray(array $data): self
    {
        return new self(
            table: $data['table'] ?? '',
            uid: (int)($data['uid'] ?? 0),
            updatedAt: isset($data['updatedAt']) ? new \DateTimeImmutable($data['updatedAt']) : new \DateTimeImmutable(),
        );
    }

    public function toArray(): array
    {
        return [
            'table' => $this->table,
            'uid' => $this->uid,
            'updatedAt' => $this->updatedAt->format(\DateTimeInterface::ATOM),
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
