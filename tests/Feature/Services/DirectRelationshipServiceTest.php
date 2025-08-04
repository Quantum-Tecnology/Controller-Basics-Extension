<?php

declare(strict_types = 1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations;
use QuantumTecnology\ControllerBasicsExtension\Services\RelationshipService;

beforeEach(function (): void {
    $this->service = app(RelationshipService::class);
});

// This test directly targets the specific lines in the RelationshipService class that are not covered
it('directly tests the uncovered lines in the RelationshipService class', function (): void {
    // Create a test model that doesn't interact with the database
    $model = new class() extends Model {
        // Disable timestamps to avoid database operations
        public $timestamps = false;

        // Prevent the model from being saved to the database
        public function save(array $options = [])
        {
            // Override save to do nothing
            return true;
        }

        // Method that exists but doesn't return a Relation - for testing line 32
        public function notARelation(): string
        {
            return 'not a relation';
        }

        // Method that returns a custom relation type - for testing lines 42-43
        public function customRelation(): Relations\Relation
        {
            return new class($this->newQuery(), $this) extends Relations\Relation {
                public function addConstraints(): void
                {
                }

                public function addEagerConstraints(array $models): void
                {
                }

                public function initRelation(array $models, $relation)
                {
                    return $models;
                }

                public function match(array $models, $results, $relation)
                {
                    return $models;
                }

                public function getResults()
                {
                    return null;
                }
            };
        }

        // Override fresh to return the model itself to avoid database operations
        public function fresh($with = [])
        {
            return $this;
        }
    };

    // Create a reflection class for the RelationshipService
    $reflectionClass = new ReflectionClass(RelationshipService::class);

    // Get the protected splitAttributesAndRelations method
    $splitMethod = $reflectionClass->getMethod('splitAttributesAndRelations');
    $splitMethod->setAccessible(true);

    // Test the execute method directly with a non-existent relation name (line 26)
    $data1 = [
        'title'               => 'Test Model',
        'nonExistentRelation' => ['data' => 'test'], // This relation name doesn't exist as a method
    ];

    // Split the data into attributes and relations
    [$attributes1, $relations1] = $splitMethod->invoke($this->service, $model, $data1);

    // Verify that nonExistentRelation is treated as an attribute, not a relation
    expect($attributes1)->toHaveKey('nonExistentRelation')
        ->and($relations1)->not->toHaveKey('nonExistentRelation');

    // Now directly test the execute method with the non-existent relation
    // This should hit line 26 (continue if method doesn't exist)
    $result1 = $this->service->execute($model, $data1);

    // Test the execute method with a method that doesn't return a Relation (line 32)
    $data2 = [
        'title'        => 'Test Model',
        'notARelation' => ['data' => 'test'], // This method exists but doesn't return a Relation
    ];

    // Split the data into attributes and relations
    [$attributes2, $relations2] = $splitMethod->invoke($this->service, $model, $data2);

    // Verify that notARelation is treated as an attribute, not a relation
    // This is because it doesn't return a Relation instance
    expect($attributes2)->toHaveKey('notARelation')
        ->and($relations2)->not->toHaveKey('notARelation');

    // Now directly test the execute method with the non-relation method
    // This should hit line 32 (continue if not a Relation instance)
    $result2 = $this->service->execute($model, $data2);

    // Test the execute method with a custom relation type (lines 42-43)
    $data3 = [
        'title'          => 'Test Model',
        'customRelation' => ['data' => 'test'], // This method returns a custom relation type
    ];

    // Split the data into attributes and relations
    [$attributes3, $relations3] = $splitMethod->invoke($this->service, $model, $data3);

    // Verify that customRelation is treated as a relation
    expect($relations3)->toHaveKey('customRelation');

    // Now directly test the execute method with the custom relation
    // This should hit lines 42-43 (default case in match statement)
    $result3 = $this->service->execute($model, $data3);

    // If we reach here without exceptions, the test passes
    expect($result1)->toBeInstanceOf(Model::class)
        ->and($result2)->toBeInstanceOf(Model::class)
        ->and($result3)->toBeInstanceOf(Model::class);
});

// This test uses a different approach to target the uncovered lines
it('tests the uncovered lines by directly accessing the execute method', function (): void {
    // Create a model that doesn't interact with the database
    $model = new class() extends Model {
        // Disable timestamps to avoid database operations
        public $timestamps = false;

        // Prevent the model from being saved to the database
        public function save(array $options = [])
        {
            // Override save to do nothing
            return true;
        }

        // Method that exists but doesn't return a Relation - for testing line 32
        public function notARelation(): string
        {
            return 'not a relation';
        }

        // Method that returns a custom relation type - for testing lines 42-43
        public function customRelation(): Relations\Relation
        {
            return new class($this->newQuery(), $this) extends Relations\Relation {
                public function addConstraints(): void
                {
                }

                public function addEagerConstraints(array $models): void
                {
                }

                public function initRelation(array $models, $relation)
                {
                    return $models;
                }

                public function match(array $models, $results, $relation)
                {
                    return $models;
                }

                public function getResults()
                {
                    return null;
                }
            };
        }

        // Override fresh to return the model itself to avoid database operations
        public function fresh($with = [])
        {
            return $this;
        }
    };

    // Create a reflection class for the RelationshipService
    $reflectionClass = new ReflectionClass(RelationshipService::class);

    // Get the execute method
    $executeMethod = $reflectionClass->getMethod('execute');
    $executeMethod->setAccessible(true);

    // Test line 26: Skip if method doesn't exist
    // Call the execute method with a non-existent relation name
    $result1 = $executeMethod->invoke($this->service, $model, [
        'title'               => 'Test Model',
        'nonExistentRelation' => ['data' => 'test'], // This relation name doesn't exist as a method
    ]);

    // Test line 32: Skip if not a Relation instance
    // Call the execute method with a method that doesn't return a Relation
    $result2 = $executeMethod->invoke($this->service, $model, [
        'title'        => 'Test Model',
        'notARelation' => ['data' => 'test'], // This method exists but doesn't return a Relation
    ]);

    // Test lines 42-43: Default case in match statement
    // Call the execute method with a custom relation type that doesn't match any specific case
    $result3 = $executeMethod->invoke($this->service, $model, [
        'title'          => 'Test Model',
        'customRelation' => ['data' => 'test'], // This method returns a custom relation type
    ]);

    // If we reach here without exceptions, the test passes
    expect($result1)->toBeInstanceOf(Model::class)
        ->and($result2)->toBeInstanceOf(Model::class)
        ->and($result3)->toBeInstanceOf(Model::class);
});
