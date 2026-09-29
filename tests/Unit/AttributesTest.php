<?php

declare(strict_types=1);

use CalebDW\SqlEntities\Attributes\Aggregate;
use CalebDW\SqlEntities\Attributes\Arguments;
use CalebDW\SqlEntities\Attributes\Characteristics;
use CalebDW\SqlEntities\Attributes\CheckOption;
use CalebDW\SqlEntities\Attributes\Columns;
use CalebDW\SqlEntities\Attributes\Concurrent;
use CalebDW\SqlEntities\Attributes\Connection;
use CalebDW\SqlEntities\Attributes\Constraint;
use CalebDW\SqlEntities\Attributes\DependsOn;
use CalebDW\SqlEntities\Attributes\Events;
use CalebDW\SqlEntities\Attributes\Language;
use CalebDW\SqlEntities\Attributes\Loadable;
use CalebDW\SqlEntities\Attributes\Name;
use CalebDW\SqlEntities\Attributes\Recursive;
use CalebDW\SqlEntities\Attributes\Returns;
use CalebDW\SqlEntities\Attributes\Table;
use CalebDW\SqlEntities\Attributes\Timing;
use CalebDW\SqlEntities\Attributes\WithData;
use CalebDW\SqlEntities\Function_;
use CalebDW\SqlEntities\Grammars\PostgresGrammar;
use CalebDW\SqlEntities\MaterializedView;
use CalebDW\SqlEntities\Procedure;
use CalebDW\SqlEntities\Trigger;
use CalebDW\SqlEntities\View;
use Illuminate\Database\Connection as DatabaseConnection;

describe('shared attributes', function () {
    it('reads the name and connection', function () {
        $entity = new #[Connection(ReportingConnection::Reporting)] #[Name('other_schema.recent_orders')] class extends View
        {
            public function definition(): string
            {
                return 'SELECT 1';
            }
        };

        expect($entity->name())->toBe('other_schema.recent_orders')
            ->and($entity->connectionName())->toBe('reporting');
    });

    it('defaults the name from the class', function () {
        expect((new AttributeNamedView())->name())->toBe('attribute_named_view')
            ->and((new AttributeNamedView())->connectionName())->toBeNull();
    });

    it('uses the attribute while a protected property is at its default', function () {
        $entity = new PropertyOverridesAttributeView();

        expect($entity->name())->toBe('from_attribute')
            ->and($entity->connectionName())->toBe('attribute');
    });

    it('does not let a null property clear an inherited attribute', function () {
        expect((new ChildResetsConnectionView())->connectionName())->toBe('parent');
    });

    it('uses the nearest attribute', function () {
        expect((new ChildConnectionView())->connectionName())->toBe('child')
            ->and((new InheritedConnectionView())->connectionName())->toBe('parent');
    });

    it('lets a runtime value override an attribute', function () {
        $entity = new RuntimeConnectionView();

        expect($entity->connectionName())->toBe('attribute');

        $entity->setConnection('runtime');

        expect($entity->connectionName())->toBe('runtime');
    });

    it('reads list characteristics and dependencies', function () {
        $entity = new DependentView();

        expect($entity->characteristics())->toBe(['WITH SCHEMABINDING', 'SECURITY INVOKER'])
            ->and($entity->dependencies())->toBe([
                AttributeNamedView::class,
                PropertyOverridesAttributeView::class,
            ]);
    });

    it('does not merge a child list attribute with its parent', function () {
        expect((new ChildCharacteristicsView())->characteristics())->toBe(['CHILD']);
    });

    it('reads attributes from a trait', function () {
        expect((new TraitConfiguredView())->connectionName())->toBe('trait')
            ->and((new TraitConfiguredView())->dependencies())->toBe([AttributeNamedView::class]);
    });
});

describe('view attributes', function () {
    it('reads columns, the check option, and recursive', function () {
        $entity = new #[CheckOption('cascaded')] #[Columns(['id', 'name'])] #[Recursive] class extends View
        {
            public function definition(): string
            {
                return 'SELECT 1';
            }
        };

        expect($entity->columns())->toBe(['id', 'name'])
            ->and($entity->checkOption())->toBe('cascaded')
            ->and($entity->isRecursive())->toBeTrue();
    });

    it('treats a bare check option as true', function () {
        $entity = new #[CheckOption] class () extends View
        {
            public function definition(): string
            {
                return 'SELECT 1';
            }
        };

        expect($entity->checkOption())->toBeTrue();
    });

    it('compiles a view from attributes', function () {
        $grammar = new PostgresGrammar(Mockery::mock(DatabaseConnection::class));

        expect($grammar->compileCreate(new CompiledAttributeView()))->toBe(<<<'SQL'
            CREATE OR REPLACE RECURSIVE VIEW recent_orders (id, name)
            WITH SCHEMABINDING
            AS SELECT 1
            WITH CASCADED CHECK OPTION
            SQL);
    });
});

describe('materialized view attributes', function () {
    it('reads columns, with data, and concurrent', function () {
        $entity = new #[Columns(['id'])] #[Concurrent] #[WithData(false)] class extends MaterializedView
        {
            public function definition(): string
            {
                return 'SELECT 1';
            }
        };

        expect($entity->columns())->toBe(['id'])
            ->and($entity->withData())->toBeFalse()
            ->and($entity->isConcurrent())->toBeTrue();
    });
});

describe('function and procedure attributes', function () {
    it('reads function configuration', function () {
        $entity = new #[Aggregate] #[Arguments(['integer', 'integer'])] #[Language('c')] #[Loadable] #[Returns('integer')] class extends Function_
        {
            public function definition(): string
            {
                return 'c_add';
            }
        };

        expect($entity->aggregate())->toBeTrue()
            ->and($entity->arguments())->toBe(['integer', 'integer'])
            ->and($entity->language())->toBe('c')
            ->and($entity->loadable())->toBeTrue()
            ->and($entity->returns())->toBe('integer');
    });

    it('reads procedure configuration', function () {
        $entity = new #[Arguments(['message text'])] #[Language('plpgsql')] class extends Procedure
        {
            public function definition(): string
            {
                return 'NULL';
            }
        };

        expect($entity->arguments())->toBe(['message text'])
            ->and($entity->language())->toBe('plpgsql');
    });

    it('throws when a required function property is missing', function () {
        $entity = new class () extends Function_
        {
            public function definition(): string
            {
                return 'SELECT 1';
            }
        };

        expect(fn () => $entity->returns())->toThrow(Error::class);
    });
});

describe('trigger attributes', function () {
    it('reads trigger configuration', function () {
        $entity = new #[Constraint] #[Events(['INSERT', 'UPDATE'])] #[Table('accounts')] #[Timing('AFTER')] class extends Trigger
        {
            public function definition(): string
            {
                return 'EXECUTE FUNCTION record_account_audit()';
            }
        };

        expect($entity->constraint())->toBeTrue()
            ->and($entity->events())->toBe(['INSERT', 'UPDATE'])
            ->and($entity->table())->toBe('accounts')
            ->and($entity->timing())->toBe('AFTER');
    });

    it('lets a flag attribute disable an inherited flag', function () {
        expect((new PlainChildTrigger())->constraint())->toBeTrue()
            ->and((new DisabledConstraintTrigger())->constraint())->toBeFalse();
    });

    it('throws when a required trigger property is missing', function () {
        $entity = new class () extends Trigger
        {
            public function definition(): string
            {
                return 'EXECUTE FUNCTION record_account_audit()';
            }
        };

        expect(fn () => $entity->table())->toThrow(Error::class);
    });
});

enum ReportingConnection: string
{
    case Reporting = 'reporting';
}

class AttributeNamedView extends View
{
    public function definition(): string
    {
        return 'SELECT 1';
    }
}

#[Connection('attribute')]
#[Name('from_attribute')]
class PropertyOverridesAttributeView extends View
{
    protected ?string $connection = 'property';

    protected ?string $name = 'from_property';

    public function definition(): string
    {
        return 'SELECT 1';
    }
}

#[Connection('parent')]
class ParentConnectionView extends View
{
    public function definition(): string
    {
        return 'SELECT 1';
    }
}

class InheritedConnectionView extends ParentConnectionView
{
}

#[Connection('child')]
class ChildConnectionView extends ParentConnectionView
{
}

class ChildResetsConnectionView extends ParentConnectionView
{
    protected ?string $connection = null;
}

#[Connection('attribute')]
class RuntimeConnectionView extends View
{
    public function definition(): string
    {
        return 'SELECT 1';
    }

    public function setConnection(?string $connection): void
    {
        $this->connection = $connection;
    }
}

#[Characteristics(['WITH SCHEMABINDING', 'SECURITY INVOKER'])]
#[DependsOn([AttributeNamedView::class, PropertyOverridesAttributeView::class])]
class DependentView extends View
{
    public function definition(): string
    {
        return 'SELECT 1';
    }
}

#[Characteristics('PARENT')]
class ParentCharacteristicsView extends View
{
    public function definition(): string
    {
        return 'SELECT 1';
    }
}

#[Characteristics('CHILD')]
class ChildCharacteristicsView extends ParentCharacteristicsView
{
}

#[Connection('trait')]
#[DependsOn(AttributeNamedView::class)]
trait ConfiguresConnection
{
}

class TraitConfiguredView extends View
{
    use ConfiguresConnection;

    public function definition(): string
    {
        return 'SELECT 1';
    }
}

#[Characteristics('WITH SCHEMABINDING')]
#[CheckOption('cascaded')]
#[Columns(['id', 'name'])]
#[Name('recent_orders')]
#[Recursive]
class CompiledAttributeView extends View
{
    public function definition(): string
    {
        return 'SELECT 1';
    }
}

#[Constraint]
class ParentConstraintTrigger extends Trigger
{
    protected array $events = ['UPDATE'];

    protected string $table = 'accounts';

    protected string $timing = 'AFTER';

    public function definition(): string
    {
        return 'EXECUTE FUNCTION record_account_audit()';
    }
}

class PlainChildTrigger extends ParentConstraintTrigger
{
}

#[Constraint(false)]
class DisabledConstraintTrigger extends ParentConstraintTrigger
{
}
