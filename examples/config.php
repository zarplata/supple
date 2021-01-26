<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Elasticsearch\ClientBuilder;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Zp\Supple\ConfigurableInterface;
use Zp\Supple\ConfigurationProfileInterface;
use Zp\Supple\Elasticsearch\Index;
use Zp\Supple\Indexation\IdentifierResolverInterface;
use Zp\Supple\Indexation\RouterInterface;
use Zp\Supple\Indexation\RoutingInterface;
use Zp\Supple\Supple;
use Zp\Supple\SuppleBuilder;

return static function (bool $logging): Supple {
    $client = ClientBuilder::create()->setHosts([getenv('ELASTICSEARCH_URL') ?: 'http://127.0.0.1:9200']);

    if ($logging) {
        $logger = new Logger('app');
        $logger->pushHandler(new StreamHandler('php://output'));
        $client->setLogger($logger);
    }

    $analysisProfile = new class implements ConfigurationProfileInterface {
        public function configure(ConfigurableInterface $configuration): void
        {
            $configuration->addAnalysis(
                'tokenizer',
                'punctuation',
                ["type" => "pattern", "pattern" => "[ .,!?]"]
            );
            $configuration->addAnalysis(
                'analyzer',
                'my_custom_analyzer',
                ["type" => "custom", "tokenizer" => "punctuation", "filter" => ["lowercase"]]
            );
        }
    };

    $clusterProfile = new class implements ConfigurationProfileInterface {
        public function configure(ConfigurableInterface $configuration): void
        {
            $configuration->addSetting('index.number_of_shards', '2');
            $configuration->addSetting('index.number_of_replicas', '1');
        }
    };

    $supple = SuppleBuilder::create()
        ->useElasticsearchClient($client->build())
        ->build();

    $supple->registerDocument(Post::class, $analysisProfile, $clusterProfile)
        ->toDynamicIndices(
            static function () {
                $indices = [];
                $currentYear = (int)date('Y');
                for ($year = 2020; $year <= $currentYear; $year++) {
                    $indices[] = sprintf('posts-%d', $year);
                }
                return $indices;
            }
        )
        ->useDocumentID(
            new class implements IdentifierResolverInterface {
                /**
                 * @param object&Post $document
                 * @return string|null
                 */
                public function resolve(object $document): ?string
                {
                    return (string)$document->id;
                }
            }
        )
        ->useIndexRouter(
            new class () implements RouterInterface {
                /**
                 * @param object&Post $document
                 * @param Index $index
                 * @param RoutingInterface $routing
                 */
                public function route(object $document, Index $index, RoutingInterface $routing): void
                {
                    if ($index->matchNameTo(sprintf('posts-%s', $document->createdAt->format('Y')))) {
                        $routing->indexTo($index);
                    } else {
                        $routing->deleteFrom($index);
                    }
                }
            }
        )
    ;

    return $supple;
};
