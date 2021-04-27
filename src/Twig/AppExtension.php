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

namespace App\Twig;

use App\Generator\UrlGenerator;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    /** @var RequestStack */
    private $requestStack;

    /** @var UrlGenerator */
    private $generator;

    public function __construct(RequestStack $requestStack, UrlGenerator $generator)
    {
        $this->requestStack = $requestStack;
        $this->generator = $generator;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('absolute_app_url', [$this, 'getAbsoluteAppUrl']),
            new TwigFunction('getActiveMenu', [$this, 'getActiveMenu']),
        ];
    }

    public function getAbsoluteAppUrl(string $name, array $parameters = []): string
    {
        return $this->generator->generate($name, $parameters);
    }

    public function getActiveMenu(): ?string
    {
        $route = $this->requestStack->getCurrentRequest()->attributes->get('_route');

        switch ($route) {
            case 'platform_message_launch_lti_resource_link':
            case 'tool_message_launch':
            case 'platform_message_launch_deep_linking':
            case 'platform_message_deep_linking_return':
            case 'platform_message_launch_proctoring':
            case 'tool_message_launch_proctoring':
            case 'platform_message_proctoring_return':
                return 'message';
            case 'tool_service_client':
                return 'service';
            case 'platform_basic_outcome_list':
            case 'platform_basic_outcome_delete':
            case 'platform_nrps_list_memberships':
            case 'platform_nrps_create_membership':
            case 'platform_nrps_view_membership':
            case 'platform_nrps_edit_membership':
            case 'platform_nrps_delete_membership':
            case 'platform_proctoring_list_assessments':
            case 'platform_proctoring_create_assessment':
            case 'platform_proctoring_view_assessment':
            case 'platform_proctoring_edit_assessment':
            case 'platform_proctoring_delete_assessment':
                return 'platform';
            default:
                return null;
        }
    }
}
