# supple

Supple is a framework agnostic document indexation and migration tool for Elasticsearch.

## Installation

```bash
composer require zp/supple
```

## Usage

See `examples/example.php`.

## Annotations

```
use Zp\Supple\Annotation as Elastic;
```

### @Elastic\Index

TODO

### @Elastic\IndexTemplate

TODO

### @Elastic\IndexAnalysis

TODO

### @Elastic\IndexMapping

TODO

### @Elastic\IndexSetting

TODO

### @Elastic\ID

TODO

### @Elastic\Mapping

TODO

### @Elastic\EmbeddedMapping

TODO

### @Elastic\Ignore

TODO

### JMSSerializer

Under the hood, for mapping document to JSON used library [JMSSerializer](https://jmsyst.com/libs/serializer), so you could use their annotations.
