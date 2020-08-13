<?php

namespace DeliciousBrains\WP_Offload_Media\Aws3\Aws\EndpointDiscovery;

use DeliciousBrains\WP_Offload_Media\Aws3\Aws\AbstractConfigurationProvider;
use DeliciousBrains\WP_Offload_Media\Aws3\Aws\CacheInterface;
use DeliciousBrains\WP_Offload_Media\Aws3\Aws\ConfigurationProviderInterface;
use DeliciousBrains\WP_Offload_Media\Aws3\Aws\EndpointDiscovery\Exception\ConfigurationException;
use DeliciousBrains\WP_Offload_Media\Aws3\GuzzleHttp\Promise;
use DeliciousBrains\WP_Offload_Media\Aws3\GuzzleHttp\Promise\PromiseInterface;
/**
 * A configuration provider is a function that returns a promise that is
 * fulfilled with a {@see \Aws\EndpointDiscovery\ConfigurationInterface}
 * or rejected with an {@see \Aws\EndpointDiscovery\Exception\ConfigurationException}.
 *
 * <code>
 * use Aws\EndpointDiscovery\ConfigurationProvider;
 * $provider = ConfigurationProvider::defaultProvider();
 * // Returns a ConfigurationInterface or throws.
 * $config = $provider()->wait();
 * </code>
 *
 * Configuration providers can be composed to create configuration using
 * conditional logic that can create different configurations in different
 * environments. You can compose multiple providers into a single provider using
 * {@see Aws\EndpointDiscovery\ConfigurationProvider::chain}. This function
 * accepts providers as variadic arguments and returns a new function that will
 * invoke each provider until a successful configuration is returned.
 *
 * <code>
 * // First try an INI file at this location.
 * $a = ConfigurationProvider::ini(null, '/path/to/file.ini');
 * // Then try an INI file at this location.
 * $b = ConfigurationProvider::ini(null, '/path/to/other-file.ini');
 * // Then try loading from environment variables.
 * $c = ConfigurationProvider::env();
 * // Combine the three providers together.
 * $composed = ConfigurationProvider::chain($a, $b, $c);
 * // Returns a promise that is fulfilled with a configuration or throws.
 * $promise = $composed();
 * // Wait on the configuration to resolve.
 * $config = $promise->wait();
 * </code>
 */
class ConfigurationProvider extends \DeliciousBrains\WP_Offload_Media\Aws3\Aws\AbstractConfigurationProvider implements \DeliciousBrains\WP_Offload_Media\Aws3\Aws\ConfigurationProviderInterface
{
    const DEFAULT_ENABLED = false;
    const DEFAULT_CACHE_LIMIT = 1000;
    const ENV_ENABLED = 'AWS_ENDPOINT_DISCOVERY_ENABLED';
    const ENV_ENABLED_ALT = 'AWS_ENABLE_ENDPOINT_DISCOVERY';
    const ENV_PROFILE = 'AWS_PROFILE';
    public static $cacheKey = 'aws_cached_endpoint_discovery_config';
    protected static $interfaceClass = \DeliciousBrains\WP_Offload_Media\Aws3\Aws\EndpointDiscovery\ConfigurationInterface::class;
    protected static $exceptionClass = \DeliciousBrains\WP_Offload_Media\Aws3\Aws\EndpointDiscovery\Exception\ConfigurationException::class;
    /**
     * Create a default config provider that first checks for environment
     * variables, then checks for a specified profile in the environment-defined
     * config file location (env variable is 'AWS_CONFIG_FILE', file location
     * defaults to ~/.aws/config), then checks for the "default" profile in the
     * environment-defined config file location, and failing those uses a default
     * fallback set of configuration options.
     *
     * This provider is automatically wrapped in a memoize function that caches
     * previously provided config options.
     *
     * @param array $config
     *
     * @return callable
     */
    public static function defaultProvider(array $config = [])
    {
        $configProviders = [self::env(), self::ini(), self::fallback()];
        $memo = self::memoize(call_user_func_array('self::chain', $configProviders));
        if (isset($config['endpoint_discovery']) && $config['endpoint_discovery'] instanceof CacheInterface) {
            return self::cache($memo, $config['endpoint_discovery'], self::$cacheKey);
        }
        return $memo;
    }
    /**
     * Provider that creates config from environment variables.
     *
     * @param $cacheLimit
     * @return callable
     */
    public static function env($cacheLimit = self::DEFAULT_CACHE_LIMIT)
    {
        return function () use($cacheLimit) {
            // Use config from environment variables, if available
            $enabled = getenv(self::ENV_ENABLED);
            if ($enabled === false || $enabled === '') {
                $enabled = getenv(self::ENV_ENABLED_ALT);
            }
            if ($enabled !== false && $enabled !== '') {
                return \DeliciousBrains\WP_Offload_Media\Aws3\GuzzleHttp\Promise\promise_for(new \DeliciousBrains\WP_Offload_Media\Aws3\Aws\EndpointDiscovery\Configuration($enabled, $cacheLimit));
            }
            return self::reject('Could not find environment variable config' . ' in ' . self::ENV_ENABLED);
        };
    }
    /**
     * Fallback config options when other sources are not set.
     *
     * @return callable
     */
    public static function fallback()
    {
        return function () {
            return \DeliciousBrains\WP_Offload_Media\Aws3\GuzzleHttp\Promise\promise_for(new \DeliciousBrains\WP_Offload_Media\Aws3\Aws\EndpointDiscovery\Configuration(self::DEFAULT_ENABLED, self::DEFAULT_CACHE_LIMIT));
        };
    }
    /**
     * Config provider that creates config using a config file whose location
     * is specified by an environment variable 'AWS_CONFIG_FILE', defaulting to
     * ~/.aws/config if not specified
     *
     * @param string|null $profile  Profile to use. If not specified will use
     *                              the "default" profile.
     * @param string|null $filename If provided, uses a custom filename rather
     *                              than looking in the default directory.
     * @param int         $cacheLimit
     *
     * @return callable
     */
    public static function ini($profile = null, $filename = null, $cacheLimit = self::DEFAULT_CACHE_LIMIT)
    {
        $filename = $filename ?: self::getDefaultConfigFilename();
        $profile = $profile ?: (getenv(self::ENV_PROFILE) ?: 'default');
        return function () use($profile, $filename, $cacheLimit) {
            if (!is_readable($filename)) {
                return self::reject("Cannot read configuration from {$filename}");
            }
            $data = \DeliciousBrains\WP_Offload_Media\Aws3\Aws\parse_ini_file($filename, true);
            if ($data === false) {
                return self::reject("Invalid config file: {$filename}");
            }
            if (!isset($data[$profile])) {
                return self::reject("'{$profile}' not found in config file");
            }
            if (!isset($data[$profile]['endpoint_discovery_enabled'])) {
                return self::reject("Required endpoint discovery config values \n                    not present in INI profile '{$profile}' ({$filename})");
            }
            return \DeliciousBrains\WP_Offload_Media\Aws3\GuzzleHttp\Promise\promise_for(new \DeliciousBrains\WP_Offload_Media\Aws3\Aws\EndpointDiscovery\Configuration($data[$profile]['endpoint_discovery_enabled'], $cacheLimit));
        };
    }
    /**
     * Unwraps a configuration object in whatever valid form it is in,
     * always returning a ConfigurationInterface object.
     *
     * @param  mixed $config
     * @return ConfigurationInterface
     * @throws \InvalidArgumentException
     */
    public static function unwrap($config)
    {
        if (is_callable($config)) {
            $config = $config();
        }
        if ($config instanceof PromiseInterface) {
            $config = $config->wait();
        }
        if ($config instanceof ConfigurationInterface) {
            return $config;
        } elseif (is_array($config) && isset($config['enabled'])) {
            if (isset($config['cache_limit'])) {
                return new \DeliciousBrains\WP_Offload_Media\Aws3\Aws\EndpointDiscovery\Configuration($config['enabled'], $config['cache_limit']);
            }
            return new \DeliciousBrains\WP_Offload_Media\Aws3\Aws\EndpointDiscovery\Configuration($config['enabled'], self::DEFAULT_CACHE_LIMIT);
        }
        throw new \InvalidArgumentException('Not a valid endpoint_discovery ' . 'configuration argument.');
    }
}