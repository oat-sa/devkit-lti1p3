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
use App\Kernel;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    /** @var RequestStack */
    private $requestStack;

    /** @var UrlGenerator */
    private $generator;

    /** @var ParameterBagInterface */
    private $parameterBag;

    public function __construct(RequestStack $requestStack, UrlGenerator $generator, ParameterBagInterface $parameterBag)
    {
        $this->requestStack = $requestStack;
        $this->generator = $generator;
        $this->parameterBag = $parameterBag;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('absolute_app_url', [$this, 'getAbsoluteAppUrl']),
            new TwigFunction('get_active_menu', [$this, 'getActiveMenu']),
            new TwigFunction('get_php_version', [$this, 'getPhpVersion']),
            new TwigFunction('get_symfony_version', [$this, 'getSymfonyVersion']),
            new TwigFunction('get_vendor_versions', [$this, 'getVendorVersions']),
        ];
    }

    public function getFilters()
    {
        return [
            new TwigFilter('scrap_app_dom', [$this, 'scrapAppDom']),
            new TwigFilter('url_base64_encode', [$this, 'urlBase64Encode']),
        ];
    }

    public function getAbsoluteAppUrl(string $name, array $parameters = []): string
    {
        return $this->generator->generate($name, $parameters);
    }

    public function getPhpVersion(): string
    {
        $phpVersion = PHP_VERSION;
        $parts = explode('.', $phpVersion);
        $phpMinorVersion = $parts[0].'.'.$parts[1];

        return $phpMinorVersion.'.'.explode('-', $parts[2])[0] ?? '';
    }

    public function getSymfonyVersion(): string
    {
        return Kernel::VERSION;
    }

    public function getVendorVersions(): array
    {
        $installedVendors = require_once $this->parameterBag->get('application_vendors');
        $installedVendorsVersions = $installedVendors['versions'] ?? [];

        $vendors = [
            'oat-sa/bundle-lti1p3',
            'oat-sa/lib-lti1p3-core',
            'oat-sa/lib-lti1p3-ags',
            'oat-sa/lib-lti1p3-basic-outcome',
            'oat-sa/lib-lti1p3-deep-linking',
            'oat-sa/lib-lti1p3-nrps',
            'oat-sa/lib-lti1p3-proctoring',
        ];

        $vendorVersions = [];

        foreach ($vendors as $vendor) {
            $vendorVersions[$vendor] = $installedVendorsVersions[$vendor]['pretty_version'] ?? 'n/a';
        }

        return $vendorVersions;
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
            case 'platform_ags_list_line_items':
            case 'platform_ags_create_line_item':
            case 'platform_ags_view_line_item':
            case 'platform_ags_edit_line_item':
            case 'platform_ags_delete_line_item':
                return 'platform';
            default:
                return null;
        }
    }

    public function scrapAppDom(string $dom): string
    {
        $crawler = new Crawler($dom);

        $filter = $crawler->filter('body.lti1p3-demo-app div.lti1p3-demo-app-content');

        if ($filter->count() !== 0) {
            return $filter->first()->html();
        }

        return $dom;
    }

    public function urlBase64Encode(string $value): string
    {
        return urlencode(base64_encode($value));
    }
}
