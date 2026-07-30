<?php

/**
 * SPDX-FileCopyrightText: 2019-2026 Open Assessment Technologies S.A.
 * Copyright (C) 2026 (original work) Open Assessment Technologies S.A.
 *
 * SPDX-License-Identifier: AGPL-3.0-only OR LicenseRef-TAO-Commercial-License
 */

declare(strict_types=1);

namespace App\Proctoring;

use InvalidArgumentException;
use JsonSerializable;
use OAT\Library\Lti1p3Proctoring\Model\AcsControlInterface;
use OAT\Library\Lti1p3Proctoring\Model\AcsControlResultInterface;

class Assessment implements JsonSerializable
{
    /** @var string */
    private $identifier;

    /** @var string */
    private $status;

    /** @var AcsControlInterface[] */
    private $controls;

    public function __construct(string $identifier, string $status, array $controls = [])
    {
        $this->identifier = $identifier;
        $this->setStatus($status);
        $this->controls = $controls;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function setIdentifier(string $identifier): Assessment
    {
        $this->identifier = $identifier;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @throw InvalidArgumentException
     */
    public function setStatus(string $status): Assessment
    {
        if (!in_array($status, AcsControlResultInterface::SUPPORTED_STATUSES)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Assessment status %s is not supported. Supported statuses: %s',
                    $status,
                    implode(', ', AcsControlResultInterface::SUPPORTED_STATUSES)
                )
            );
        }

        $this->status = $status;

        return $this;
    }

    public function getControls(): array
    {
        return $this->controls;
    }

    public function addControl(AcsControlInterface $control): Assessment
    {
        $this->controls[] = $control;

        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->identifier,
            'status' => $this->status
        ];
    }
}
