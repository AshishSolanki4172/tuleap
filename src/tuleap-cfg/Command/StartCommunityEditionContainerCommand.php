<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 *
 */

declare(strict_types=1);

namespace TuleapCfg\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tuleap\BuildVersion\FlavorFinderFromFilePresence;
use Tuleap\BuildVersion\VersionPresenter;
use TuleapCfg\Command\Docker\DataPersistence;
use TuleapCfg\Command\Docker\Postfix;
use TuleapCfg\Command\Docker\Rsyslog;
use TuleapCfg\Command\Docker\SSHDaemon;
use TuleapCfg\Command\Docker\Supervisord;
use TuleapCfg\Command\Docker\Tuleap;

final class StartCommunityEditionContainerCommand extends Command
{
    private const PERSISTENT_DATA = [
        '/etc/pki/tls/private/localhost.key.pem',
        '/etc/pki/tls/certs/localhost.cert.pem',
        '/etc/tuleap',
        '/etc/ssh/ssh_host_ecdsa_key',
        '/etc/ssh/ssh_host_ed25519_key',
        '/etc/ssh/ssh_host_ecdsa_key.pub',
        '/etc/ssh/ssh_host_ed25519_key.pub',
        '/etc/ssh/ssh_host_rsa_key',
        '/etc/ssh/ssh_host_rsa_key.pub',
        '/root/.tuleap_passwd',
        '/var/lib/gitolite',
        '/var/lib/tuleap',
    ];

    private const SUPERVISORD_UNITS = [
        Supervisord::UNIT_CROND,
        Supervisord::UNIT_SSHD,
        Supervisord::UNIT_RSYSLOG,
        Supervisord::UNIT_NGINX,
        Supervisord::UNIT_POSTFIX,
        Supervisord::UNIT_HTTPD,
        Supervisord::UNIT_FPM,
        Supervisord::UNIT_BACKEND_WORKERS,
    ];

    private ProcessFactory $process_factory;
    private DataPersistence $data_persistence;

    public function __construct(ProcessFactory $process_factory)
    {
        $this->process_factory  = $process_factory;
        $this->data_persistence = new DataPersistence($this->process_factory, ...self::PERSISTENT_DATA);

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('docker:tuleap-run')
            ->setDescription('Run Tuleap in the context of `tuleap/tuleap-community-edition` image');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $version_presenter = VersionPresenter::fromFlavorFinder(new FlavorFinderFromFilePresence());
            $output->writeln(sprintf('<info>Start init sequence for %s</info>', $version_presenter->getFullDescriptiveVersion()));

            $tuleap = new Tuleap($this->process_factory);
            if (! $this->data_persistence->isThereAnyData()) {
                $tuleap_fqdn = $this->getStringFromEnvironment('TULEAP_FQDN');
                if ($tuleap_fqdn === '') {
                    throw new \RuntimeException('TULEAP_FQDN environment variable must be set');
                }
                $this->installTuleap($output, $tuleap, $tuleap_fqdn);
                $this->data_persistence->store($output);
                $this->data_persistence->restore($output);
            } else {
                $this->data_persistence->restore($output);
                $tuleap_fqdn = $tuleap->update($output);
            }

            $rsyslog = new Rsyslog();
            $rsyslog->setup($output, $tuleap_fqdn);

            $postfix = new Postfix($this->process_factory);
            $postfix->setup($output, $tuleap_fqdn);

            $supervisord = new Supervisord(...self::SUPERVISORD_UNITS);
            $supervisord->run($output);
        } catch (\Exception $exception) {
            $output->writeln(sprintf('<error>%s</error>', OutputFormatter::escape($exception->getMessage())));
            $output->writeln('Something went wrong, here is a shell to debug: ');
            $return = pcntl_exec('/bin/bash');
            if ($return !== null) {
                throw new \RuntimeException('Exec of /usr/bin/supervisord failed');
            }
        }
        return 0;
    }

    private function installTuleap(OutputInterface $output, Tuleap $tuleap, string $tuleap_fqdn): void
    {
        $ssh_daemon = new SSHDaemon($this->process_factory);

        $ssh_daemon->startDaemon($output);
        $tuleap->setup(
            $output,
            $tuleap_fqdn,
            $this->getStringFromEnvironment('DB_HOST'),
            $this->getStringFromEnvironment('DB_ADMIN_USER'),
            $this->getStringFromEnvironment('DB_ADMIN_PASSWORD'),
        );
        $this->process_factory->getProcessWithoutTimeout(['sudo', '-u', 'codendiadm', '/usr/bin/tuleap', 'plugin:install', '--all'])->mustRun();
        $ssh_daemon->shutdownDaemon($output);
    }

    private function getStringFromEnvironment(string $key): string
    {
        $value = getenv($key);
        if ($value !== false) {
            return $value;
        }
        return '';
    }
}
