<?php

/**
 * SPDX-FileCopyrightText: 2019-2026 Open Assessment Technologies S.A.
 * Copyright (C) 2026 (original work) Open Assessment Technologies S.A.
 *
 * SPDX-License-Identifier: AGPL-3.0-only OR LicenseRef-TAO-Commercial-License
 */

declare(strict_types=1);

namespace App\Ags;

use OAT\Library\Lti1p3Ags\Model\LineItem\LineItemCollection;
use OAT\Library\Lti1p3Ags\Model\LineItem\LineItemCollectionInterface;
use OAT\Library\Lti1p3Ags\Model\LineItem\LineItemInterface;
use OAT\Library\Lti1p3Ags\Repository\LineItemRepositoryInterface;
use OAT\Library\Lti1p3Ags\Repository\ResultRepositoryInterface;
use OAT\Library\Lti1p3Ags\Repository\ScoreRepositoryInterface;
use OAT\Library\Lti1p3Core\Util\Generator\IdGeneratorInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class LineItemRepository implements LineItemRepositoryInterface
{
    public const CACHE_KEY = 'lti1p3-ags-line-items';

    /** @var CacheItemPoolInterface */
    private $cache;

    /** @var RequestStack */
    private $requestStack;

    /** @var IdGeneratorInterface */
    private $generator;

    /** @var ScoreRepositoryInterface|ScoreRepository */
    private $scoreRepository;

    /** @var ResultRepositoryInterface|ResultRepository */
    private $resultRepository;

    public function __construct(
        CacheItemPoolInterface $cache,
        RequestStack $requestStack,
        IdGeneratorInterface $generator,
        ScoreRepositoryInterface $scoreRepository,
        ResultRepositoryInterface $resultRepository
    ) {
        $this->cache = $cache;
        $this->requestStack = $requestStack;
        $this->generator = $generator;
        $this->scoreRepository = $scoreRepository;
        $this->resultRepository = $resultRepository;
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

    public function findCollection(
        ?string $resourceIdentifier = null,
        ?string $resourceLinkIdentifier = null,
        ?string $tag = null,
        ?int $limit = null,
        ?int $offset = null
    ): LineItemCollectionInterface {
        $cache = $this->cache->getItem(self::CACHE_KEY);

        $foundLineItems = [];

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
        }

        return new LineItemCollection(
            array_slice($foundLineItems, $offset ?: 0, $limit),
            $limit && (($limit + $offset) < sizeof($foundLineItems))
        );
    }

    public function save(LineItemInterface $lineItem): LineItemInterface
    {
        $cache = $this->cache->getItem(self::CACHE_KEY);

        $lineItems = $cache->get();

        if (null === $lineItem->getIdentifier()) {
            $identifier = sprintf(
                '%s/%s',
                rtrim($this->requestStack->getCurrentRequest()->getUri(), '/'),
                $this->generator->generate()
            );

            $lineItem->setIdentifier($identifier);
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

            $this->scoreRepository->deleteCollectionByLineItemIdentifier($lineItemIdentifier);
            $this->resultRepository->deleteCollectionByLineItemIdentifier($lineItemIdentifier);
        }
    }
}
