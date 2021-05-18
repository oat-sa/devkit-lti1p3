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

namespace App\Ags;

use OAT\Library\Lti1p3Ags\Model\LineItem\LineItemCollection;
use OAT\Library\Lti1p3Ags\Model\LineItem\LineItemCollectionInterface;
use OAT\Library\Lti1p3Ags\Model\LineItem\LineItemInterface;
use OAT\Library\Lti1p3Ags\Repository\LineItemRepositoryInterface;
use OAT\Library\Lti1p3Core\Util\Generator\IdGeneratorInterface;
use Psr\Cache\CacheItemPoolInterface;

class LineItemRepository implements LineItemRepositoryInterface
{
    private const CACHE_KEY = 'lti1p3-ags-line-items';

    /** @var CacheItemPoolInterface */
    private $cache;

    /** @var IdGeneratorInterface */
    private $generator;

    public function __construct(CacheItemPoolInterface $cache, IdGeneratorInterface $generator)
    {
        $this->cache = $cache;
        $this->generator = $generator;
    }

    public function find(string $lineItemIdentifier): ?LineItemInterface
    {
        $cache = $this->cache->getItem(self::CACHE_KEY);

        if ($cache->isHit()) {
            $lineItems = $cache->get();

            return $lineItems[$lineItemIdentifier] ?? null;
        }

        return null;
    }

    public function findBy(
        ?string $resourceIdentifier = null,
        ?string $resourceLinkIdentifier = null,
        ?string $tag = null,
        ?int $limit = null,
        ?int $offset = null
    ): LineItemCollectionInterface {
        $cache = $this->cache->getItem(self::CACHE_KEY);

        $foundLineItems = [];
        $hasNext = false;

        if ($cache->isHit()) {

            $lineItems = $cache->get();

            /** @var LineItemInterface $lineItem */
            foreach ($lineItems as $lineItem) {
                $found = true;

                if (null !== $resourceIdentifier) {
                    $found = $found && $lineItem->getResourceIdentifier() === $resourceIdentifier;
                }

                if (null !== $resourceLinkIdentifier) {
                    $found = $found && $lineItem->getResourceLinkIdentifier() === $resourceLinkIdentifier;
                }

                if (null !== $tag) {
                    $found = $found && $lineItem->getTag() === $tag;
                }

                if ($found) {
                    $foundLineItems[] = $lineItem;
                }
            }

            $hasNext = ($limit ?: 0) >= sizeof($lineItems);
        }

        return new LineItemCollection(
            array_slice($foundLineItems, $offset ?: 0, $limit),
            $hasNext
        );
    }

    public function save(LineItemInterface $lineItem): LineItemInterface
    {
        $cache = $this->cache->getItem(self::CACHE_KEY);

        $lineItems = $cache->get();

        if (null === $lineItem->getIdentifier()) {
            $lineItem->setIdentifier($this->generator->generate());
        }

        $lineItems[$lineItem->getIdentifier()] = $lineItem;

        $cache->set($lineItems);

        $this->cache->save($cache);

        return $lineItem;
    }

    public function delete(string $lineItemIdentifier): void
    {
        $cache = $this->cache->getItem(self::CACHE_KEY);

        $lineItems = $cache->get();

        $lineItem = $this->find($lineItemIdentifier);

        if (null !== $lineItem) {
            unset($lineItems[$lineItem->getIdentifier()]);

            $cache->set($lineItems);

            $this->cache->save($cache);
        }
    }
}
