<?php

namespace App\Domain\Connectors;

use App\Domain\Connectors\Contracts\Connector;
use App\Domain\Connectors\Data\ConnectorResult;
use App\Domain\Connectors\Drivers\CarenConnector;
use App\Domain\Connectors\Drivers\CustomConnector;
use App\Domain\Connectors\Drivers\ErpenConnector;
use App\Domain\Connectors\Drivers\WordpressConnector;
use App\Domain\Connectors\Models\Connector as ConnectorModel;
use InvalidArgumentException;

/**
 * Resolves the driver implementing the connection test for a connector
 * type. Every supported type has a driver; an unknown type is a
 * programming error and throws.
 */
class ConnectorManager
{
    /**
     * The drivers, indexed by connector type.
     *
     * @var array<string, Connector>
     */
    private array $drivers;

    /**
     * Create the manager with the full driver map.
     */
    public function __construct()
    {
        $this->drivers = [
            ConnectorModel::TYPE_WORDPRESS => new WordpressConnector,
            ConnectorModel::TYPE_CAREN => new CarenConnector,
            ConnectorModel::TYPE_ERPEN => new ErpenConnector,
            ConnectorModel::TYPE_CUSTOM => new CustomConnector,
        ];
    }

    /**
     * Resolve the driver of the given connector.
     */
    public function driver(ConnectorModel $connector): Connector
    {
        return $this->drivers[$connector->type]
            ?? throw new InvalidArgumentException("Unsupported connector type [{$connector->type}].");
    }

    /**
     * Run the connection test through the type driver.
     */
    public function test(ConnectorModel $connector): ConnectorResult
    {
        return $this->driver($connector)->test($connector);
    }
}
