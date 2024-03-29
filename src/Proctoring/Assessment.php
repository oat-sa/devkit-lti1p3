<?php

/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright (c) 2019 (original work) Open Assessment Technologies SA;
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
