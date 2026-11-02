<?php

namespace Ekatra\Product\Tests\Snapshots;

use PHPUnit\Framework\TestCase;
use Ekatra\Product\EkatraSDK;

/**
 * Mapping snapshot tests
 *
 * Every input in tests/Snapshots/inputs is run through each public transform
 * method and compared byte-for-byte against tests/Snapshots/expected, which was
 * recorded from v2.1.9. Only sdkVersion and uniqid()-based fallback IDs are
 * normalized, so any change to field mapping, key order or value types fails.
 *
 * To re-record after an intentional mapping change:
 *   UPDATE_SNAPSHOTS=1 vendor/bin/phpunit tests/Snapshots
 */
class MappingSnapshotTest extends TestCase
{
    private const JSON_FLAGS = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION;

    public function testMappingOutputMatchesRecordedSnapshots()
    {
        $inputs = glob(__DIR__ . '/inputs/*.json');
        $this->assertNotEmpty($inputs, 'No snapshot inputs found');

        $update = getenv('UPDATE_SNAPSHOTS') === '1';

        foreach ($inputs as $inputPath) {
            $name = basename($inputPath, '.json');
            $input = json_decode(file_get_contents($inputPath), true);
            $actual = json_encode($this->runAllMethods($input), self::JSON_FLAGS) . "\n";
            $expectedPath = __DIR__ . '/expected/' . $name . '.json';

            if ($update) {
                if (!is_dir(dirname($expectedPath))) {
                    mkdir(dirname($expectedPath), 0777, true);
                }
                file_put_contents($expectedPath, $actual);
                continue;
            }

            $this->assertFileExists($expectedPath, "Missing snapshot for input '{$name}'");
            $this->assertSame(file_get_contents($expectedPath), $actual, "Mapping output changed for input '{$name}'");
        }
    }

    private function runAllMethods(array $input): array
    {
        $methods = [
            'transformProduct' => function ($d) { return EkatraSDK::transformProduct($d); },
            'transformVariant' => function ($d) { return EkatraSDK::transformVariant($d); },
            'smartTransformProduct' => function ($d) { return EkatraSDK::smartTransformProduct($d); },
            'smartTransformProductFlexible' => function ($d) { return EkatraSDK::smartTransformProductFlexible($d); },
            'syncProduct' => function ($d) { return EkatraSDK::syncProduct($d); },
            'syncProductData' => function ($d) { return EkatraSDK::syncProductData($d); },
            'syncProductsBatch' => function ($d) { return EkatraSDK::syncProductsBatch([$d]); },
        ];

        $results = [];
        foreach ($methods as $method => $call) {
            // Generated IDs are numbered per method so variant/size links stay checkable
            $ids = [];
            try {
                $results[$method] = $this->normalize($call($input), $ids);
            } catch (\Exception $e) {
                $results[$method] = ['exception' => get_class($e), 'message' => $e->getMessage()];
            } catch (\Throwable $e) {
                // PHP engine errors (TypeError etc.) are worded differently across PHP versions
                $results[$method] = ['error' => get_class($e)];
            }
        }

        return $results;
    }

    private function normalize($value, array &$ids)
    {
        if (is_array($value)) {
            foreach ($value as $key => $item) {
                $value[$key] = $key === 'sdkVersion' ? '{sdkVersion}' : $this->normalize($item, $ids);
            }
            return $value;
        }

        if (is_string($value)) {
            return preg_replace_callback(
                '/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}|default-[0-9a-f]{13,}/',
                function ($m) use (&$ids) {
                    if (!isset($ids[$m[0]])) {
                        $ids[$m[0]] = '{generated-id-' . (count($ids) + 1) . '}';
                    }
                    return $ids[$m[0]];
                },
                $value
            );
        }

        return $value;
    }
}
