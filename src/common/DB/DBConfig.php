<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\DB;

use Tuleap\Config\ConfigCannotBeModified;
use Tuleap\Config\ConfigKey;
use Tuleap\Config\ConfigKeyCategory;

#[ConfigKeyCategory('Database')]
final class DBConfig
{
    #[ConfigKey('Database server hostname or IP address')]
    #[ConfigCannotBeModified]
    public const CONF_HOST = 'sys_dbhost';

    #[ConfigKey('Database server port')]
    #[ConfigCannotBeModified]
    public const CONF_PORT = 'sys_dbport';

    #[ConfigKey('Database name')]
    #[ConfigCannotBeModified]
    public const CONF_DBNAME = 'sys_dbname';

    #[ConfigKey('Database application user')]
    #[ConfigCannotBeModified]
    public const CONF_DBUSER = 'sys_dbuser';

    #[ConfigKey('Database application user password')]
    #[ConfigCannotBeModified]
    public const CONF_DBPASSWORD = 'sys_dbpasswd';

    #[ConfigKey('Database is accessed with TLS')]
    #[ConfigCannotBeModified]
    public const CONF_ENABLE_SSL = 'sys_enablessl';

    #[ConfigKey('Database TLS CA')]
    #[ConfigCannotBeModified]
    public const CONF_SSL_CA = 'sys_db_ssl_ca';

    #[ConfigKey('Toggle verification of database certificate')]
    #[ConfigCannotBeModified]
    public const CONF_SSL_VERIFY_CERT = 'sys_db_ssl_verify_cert';

    #[ConfigKey('Adjust the maximum number of JOIN the mysql server can accept')]
    #[ConfigCannotBeModified]
    public const CONF_NB_MAX_JOIN = 'sys_server_join';

    public const DEFAULT_MYSQL_PORT = 3306;

    public static function isSSLEnabled(): bool
    {
        return \ForgeConfig::get(self::CONF_ENABLE_SSL) === '1';
    }

    public static function isSSLVerifyCert(): bool
    {
        return \ForgeConfig::exists(self::CONF_SSL_VERIFY_CERT) && \ForgeConfig::get(self::CONF_SSL_VERIFY_CERT) === '1';
    }

    /**
     * @throws NoCaFileException
     */
    public static function getSSLCACertFile(): string
    {
        if (\ForgeConfig::exists(self::CONF_SSL_CA) && is_file(\ForgeConfig::get(self::CONF_SSL_CA))) {
            return \ForgeConfig::get(self::CONF_SSL_CA);
        }
        throw new NoCaFileException();
    }

    public static function getPDODSN(string $database_name): string
    {
        return sprintf(
            'mysql:host=%s;port=%d;dbname=%s',
            \ForgeConfig::get(self::CONF_HOST),
            \ForgeConfig::get(self::CONF_PORT, self::DEFAULT_MYSQL_PORT),
            $database_name
        );
    }

    public static function isUsingDefaultPort(): bool
    {
        return (int) \ForgeConfig::get(self::CONF_PORT, self::DEFAULT_MYSQL_PORT) === self::DEFAULT_MYSQL_PORT;
    }
}
