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
 * Copyright (c) 2021 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace App\Command\Platform\Message;

use App\Service\Platform\Message\LtiMessageBuilder;
use InvalidArgumentException;
use OAT\Library\Lti1p3Core\Message\LtiMessageInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

class CreateMessageLaunchCommand extends Command
{
    protected static $defaultName = 'create:message:launch';

    /** @var LtiMessageBuilder */
    private $builder;

    public function __construct(LtiMessageBuilder $builder)
    {
        $this->builder = $builder;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Creates a new LTI message launch')
            ->setHelp('This command allows you to create a user...')
            ->addOption('type', 't', InputOption::VALUE_REQUIRED, 'LTI message type')
            ->addOption('parameters', 'p', InputOption::VALUE_REQUIRED, 'LTI message launch parameters (JSON encoded)');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $parameters = json_decode($input->getOption('parameters'), true);

            if (JSON_ERROR_NONE !== json_last_error()) {
                throw new InvalidArgumentException(sprintf('Invalid json, json_decode error: %s', json_last_error_msg()));
            }

            $messageType = $input->getOption('type');

            switch (ucfirst($messageType)) {
                case LtiMessageInterface::LTI_MESSAGE_TYPE_RESOURCE_LINK_REQUEST:
                    $message = $this->builder->buildLtiResourceLinkRequest($parameters);
                    break;
                default:
                    throw new InvalidArgumentException(sprintf('Invalid message type %s', $messageType));
            }

            $io->section('LTI message launch link');
            $io->text($message->toUrl());

            if ($output->isVerbose()) {
                $io->section('LTI message launch details');

                $output->writeln([
                    '<info>Url</info>',
                    '<info>---</info>',
                    $message->getUrl(),
                    '',
                    '<info>Parameters</info>',
                    '<info>----------</info>',
                ]);

                foreach ($message->getParameters() as $parameterName => $parameterValue) {
                    $output->writeln([
                        sprintf('<info>%s</info>', $parameterName),
                        $parameterValue
                    ]);
                }
            }

            return Command::SUCCESS;
        } catch (Throwable $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }
    }
}
